#!/bin/bash

print_usage_and_exit() {
  echo "Usage: $0 [OPTION]...
  Launch load tests for api service and map-reduce, and generate a csv report.

   -u       base url (default: 'http://web:8080')
   -n       n values to use in the tests (default: '10 100 200')
   -c       number of concurrent requests (default: 50)
   -q       requests per second (0 for unlimited, default: 0)
   -d       delete single_data records from raw db before every test
   -r       number of tests with same n (default: 1)
   -s       max number of values computed by a single map reduce
                (default: 10000000)

  Example:
    $0 -n '100 200 400' -q 4 -c 5 -r 10 " | sed 's/^  //g'
  exit 0
}

# number of concurrent requests
c=50
# ranges of requests
n='10 100 200'
# requests per second (0 for unlimited)
q=0
# repetitions
r=1
# max number of values computed by a single map reduce
s=10000000

# endpoint
BASE_URL='http://web:8080'

# parse command line options
while getopts 'hdn:c:q:r:s:u:' opt; do
  case ${opt} in
    h)
      # print usage with -h
      print_usage_and_exit ;;
    d)
      delete=1 ;;
    u)
      BASE_URL="${OPTARG}" ;;
    *)
      # store the option in the appropriate variable
      eval "${opt}=\"${OPTARG}\"" ;;
  esac
done

# write headers
echo 'r,n,q,200,99% in,req/s' > report_api_table.csv
echo 'r,n,map,reduce,total' > report_map-reduce_table.csv

for i in $(seq 1 ${r}); do
  for j in ${n}; do
    echo ${i}_${j}
    # delete db if -d is used
    if [[ $delete == 1 ]]; then
      docker run --net=srs_default --rm srs_raw-cli psql -c 'DELETE FROM single_data;'
    fi
    # launch api load tests
    ./load_tests.sh -u "${BASE_URL}" -n ${j} -c ${c} -q ${q} -l 'API'
    # rotate files
    mv report_api "report_api_${i}_${j}"
    mv report_table.csv "report_table_${i}_${j}.csv"
    # run map-reduce
    docker run --net=srs_default --rm srs_map-reduce php semi_parallel_updater.php \
      -s ${s} > "report_map-reduce_${i}_${j}"
    # writing api line
    tail -n 1 "report_table_${i}_${j}.csv" | sed "s/API/${i}/g" >> report_api_table.csv
    # parsing map, reduce, and total time
    file="report_map-reduce_${i}_${j}"
    map=$(grep '##PROFILE## MAP-MATCHING' ${file} | awk '{print $9}')
    reduce=$(grep '##PROFILE## LOCAL-AGGREGATION' ${file} | awk '{print $9}')
    total=$(grep '##PROFILE## OVERALL' ${file} | awk '{print $10}')
    # writing map-reduce line, removing those damned ^M
    echo "${i},${j},${map},${reduce},${total}" | tr -d $'\r' >> report_map-reduce_table.csv
  done
done
