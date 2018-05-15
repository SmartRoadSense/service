#!/bin/bash

print_usage_and_exit() {
  echo "Usage: $0 [OPTION]...
  Launch load tests for SRS services and generates a csv report.

   -u       base url (default: 'http://web:8080')
   -l       services (default: 'API WS WS_COUNT TILES')
   -n       number of requests (default: 200)
   -c       number of concurrent requests (default: 50)
   -q       requests per second (0 for unlimited, default: 0)
   -p       launch tests in parallel

  Example:
    $0 -n 100 -c 50 -q 50 -l 'API'" | sed 's/^  //g'
  exit 0
}

# number of concurrent requests
c=50
# number of total requests
n=200
# requests per second (0 for unlimited)
q=0
# services to test
l='API WS WS_COUNT TILES'

# endpoint
BASE_URL='http://127.0.0.1:80'

# parse command line options
while getopts 'hl:n:c:q:u:p' opt; do
  case ${opt} in
    h)
      # print usage with -h
      print_usage_and_exit;;
    p)
      # set parallel execution
      parallel=1 ;;
    u)
      BASE_URL="${OPTARG}" ;;
    *)
      # store the option in the appropriate variable
      eval "${opt}=\"${OPTARG}\"" ;;
  esac
done

# tester
BOOM='./boom'

# report name prefix
BASE_FILE='report'

# API params
API_PAYLOAD='payload.json'
API_PARAMS="-n ${n} -q ${q} -c ${c} -m POST -d @${API_PAYLOAD} ${BASE_URL}/api/data/"
API_FILE="${BASE_FILE}_api"

# base params for WS WS_COUNT and TILES
BASE_PARAMS="-n ${n} -q ${q} -c ${c} -m GET ${BASE_URL}"

# WS params
WS_PARAMS="${BASE_PARAMS}/ws/?bbox=11.66650390625,45.67450561310647,11.6904296875,45.69180939895816\&zoom_level=14"
WS_FILE="${BASE_FILE}_ws"

# WS_COUNT params
WS_COUNT_PARAMS="${BASE_PARAMS}/ws/count.php"
WS_COUNT_FILE="${BASE_FILE}_ws_count"

# TILES params
TILES_PARAMS="${BASE_PARAMS}/api/v1/tiles/14/8723/5849/"
TILES_FILE="${BASE_FILE}_tiles"

print_and_exec() {
  # render variable names
  local params="$1_PARAMS"
  local file="$1_FILE"

  # render options from variables
  local file=${!file}
  local cmd="${BOOM} ${!params}"

  # write the command in the top of the report
  echo "\$ ${cmd}" > ${file}

  # remove 'Boooooooooooooooooom' from output
  cmd="${cmd} | grep -v -E 'Bo*m|% $' >> ${file}"

  # exec
  if [[ ${parallel} == 1 ]]; then
    eval ${cmd} &
  else
    eval ${cmd}
  fi
}


generate_csv() {
  echo "service,n,q,200,99% in,req/s" > report_table.csv

  for x in $l; do
    # render the name of the variable containing filename
    local file="${x}_FILE"

    # render filename
    local file="${!file}"

    # render column
    local service=${x}
    local n=$(head -n 1 ${file} | cut -d ' ' -f 4)
    local q=$(head -n 1 ${file} | cut -d ' ' -f 6)
    local success=$(grep '\[200\]' ${file} | awk '{print $2}')
    local nnpercent=$(grep '99%' ${file} | awk '{print $3 " " $4}')
    local reqs=$(grep 'Requests/sec:' ${file} | awk '{print $2}')

    # write column
    echo "${service},${n},${q},${success},${nnpercent},${reqs}" >> report_table.csv
  done
}

# launch the tests
for x in ${l}; do
  echo "Testing ${x} ..."
  print_and_exec ${x};
done

# wait for jobs completion in parallel execution
if [[ ${parallel} == 1 ]]; then
  for job in $(jobs -p); do
    wait $job
  done
fi

generate_csv
