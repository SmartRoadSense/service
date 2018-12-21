package main

import (
	"database/sql"
	"flag"
	"fmt"
	_ "github.com/lib/pq"
	"log"
	"os"
	"time"
)

// Getenv returns the value of the environment variable
// with name provided in key, or the provided defaultValue
func Getenv(key string, defaultValue string) string {
	value := os.Getenv(key)
	if value == "" {
		value = defaultValue
	}
	return value
}

// checkError logs and exits the program if error are
// found on the err param returned by postgres
func checkError(err error, db map[string]string) {
	if err != nil {
		log.Printf("Error on db: ")
		log.Println(db)
		// this executes exit(1)
		log.Fatal(err)
	}
}

func main() {
	startTime := time.Now()

	exit := flag.Bool("exit", false, "Exit the program with return code 0")
	flag.Parse()
	if *exit {
		os.Exit(0)
	}

	// create and init databases params
	dbSet := make(map[string]map[string]string)
	for _, key := range []string{"raw", "agg", "meta"} {
		dbSet[key] = make(map[string]string)
	}

	// raw database
	dbSet["raw"]["host"] = Getenv("RAW_DB_HOST", "raw-db")
	dbSet["raw"]["name"] = Getenv("RAW_DB_NAME", "srs_raw_db")
	dbSet["raw"]["user"] = Getenv("RAW_DB_USER", "crowd4roads_sw")
	dbSet["raw"]["pass"] = Getenv("RAW_DB_PASS", "password")
	dbSet["raw"]["port"] = Getenv("RAW_DB_PORT", "5432")
	dbSet["raw"]["query"] = "select (select count(*) from single_data) + " +
		"(select count(*) from single_data_old)"

	// aggregate database
	dbSet["agg"]["host"] = Getenv("AGG_DB_HOST", "agg-db")
	dbSet["agg"]["name"] = Getenv("AGG_DB_NAME", "srs_agg_db")
	dbSet["agg"]["user"] = Getenv("AGG_DB_USER", "crowd4roads_sw")
	dbSet["agg"]["pass"] = Getenv("AGG_DB_PASS", "password")
	dbSet["agg"]["port"] = Getenv("AGG_DB_PORT", "5432")
	dbSet["agg"]["query"] = "SELECT COUNT(*) FROM current;"

	// meta database
	dbSet["meta"]["host"] = Getenv("META_DB_HOST", "agg-db")
	dbSet["meta"]["name"] = Getenv("META_DB_NAME", "srs_agg_db")
	dbSet["meta"]["user"] = Getenv("META_DB_USER", "crowd4roads_sw")
	dbSet["meta"]["pass"] = Getenv("META_DB_PASS", "password")
	dbSet["meta"]["port"] = Getenv("META_DB_PORT", "5432")
	dbSet["meta"]["query"] = "INSERT INTO count (raw, aggregate) VALUES (%d, %d);"

	// counters
	counts := make(map[string]int)
	counts["raw"] = 0
	counts["agg"] = 0

	// template string for db connection strings
	template := "host=%s dbname=%s user=%s password=%s sslmode=disable"

	// loop trough databases
	// NOTE: keys are harcoded to preserve the right order
	for _, dbName := range []string{"raw", "agg", "meta"} {
		localStartTime := time.Now()
		db := dbSet[dbName]
		// render the connection string
		connString := fmt.Sprintf(template,
			db["host"], db["name"], db["user"], db["pass"], db["port"])

		// print params and open the connection
		log.Println(connString)
		conn, err := sql.Open("postgres", connString)

		// check for connection errors
		checkError(err, db)

		if dbName == "meta" {
			// render the insert query from the counts
			db["query"] = fmt.Sprintf(db["query"],
				counts["raw"], counts["agg"])
		}

		// print and exec the query
		log.Println(db["query"])
		rows, err := conn.Query(db["query"])

		// check for errors in the query
		checkError(err, db)

		if dbName != "meta" {
			// save the counts
			var tmp int
			rows.Next()
			rows.Scan(&tmp)
			counts[dbName] = tmp
		}

		conn.Close()
		log.Printf("Operation on %s executed in %s\n\n",
			dbName, time.Since(localStartTime))
	}

	log.Printf("Total time %s\n", time.Since(startTime))
}
