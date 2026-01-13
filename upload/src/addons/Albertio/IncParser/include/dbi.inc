#if defined _dbi_included
	#endinput
#endif
#define _dbi_included

// You can't include SQLX first!
// there's really no reason to anyway.
#assert !defined _sqlx_included

#pragma reqclass dbi

enum Sql
{
	SQL_FAILED = 0,
	SQL_OK
};

enum Result
{
	RESULT_FAILED = -1,
	RESULT_NONE,
	RESULT_OK
};

/**
 * This will return a number equal to or below 0 on failure.
 *
 * @note   If it does fail, the error will be mirrored in dbi_error()
 *
 * @return The return value will otherwise be a resource handle, not an
 *         OK code or cell pointer.
 */
native Sql:dbi_connect(_host[], _user[], _pass[], _dbname[], _error[] = "", _maxlength = 0);

/**
 * This will do a simple query execution on the SQL server.
 *
 * @note   If it fails, it will return a number BELOW ZERO (0)
 * @note   If zero, it succeeded with NO RETURN RESULT.
 * @note   If greater than zero, make sure to call dbi_free_result() on it!
 *
 * @return The return is a handle to the result set
 */
native Result:dbi_query(Sql:_sql, _query[], any:...);

/**
 * Has the same usage as dbi_query, but this native returns by
 * reference the number of rows affected in the query. If the
 * query fails rows will be equal to -1.
 */
native Result:dbi_query2(Sql:_sql, &rows, _query[], any:...);

/**
 * Advances result pointer by one row.
 *
 * @return Returns 0 on failure or End of Results.
 */
native dbi_nextrow(Result:_result);

/**
 * Gets a field by number.
 *
 * @note Although internally fields always start from 0, 
 *       This function takes fieldnum starting from 1.
 * @note No extra params: returns int
 *       One extra param: returns Float: byref
 *       Two extra param: Stores string with length
 *
 * @return Returns 0 on failure.
 */
native dbi_field(Result:_result, _fieldnum, any:... );

/**
 * Gets a field by name.
 *
 * @note One extra param: returns Float: byref
 *       Two extra param: Stores string with length
 *
 * @return Returns 0 on failure.
 */
native dbi_result(Result:_result, _field[], any:... );

/**
 * Returns the number of rows returned from a query
 */
native dbi_num_rows(Result:_result);

/**
 * Frees memory used by a result handle. Do this or get memory leaks.
 */
native dbi_free_result(&Result:result);

/**
 * Closes a database handle. Internally, it will also 
 * mark the handle as free, so this particular handle may
 * be re-used in the future to save time.
 */
native dbi_close(&Sql:_sql);

/**
 * Returns an error message set.
 *
 * @note For PGSQL and MySQL, this is a direct error return from the
 *       database handle/API.
 * @note For MSSQL, it returns the last error message found from a 
 *       thrown exception.
 */
native dbi_error(Sql:_sql, _error[], _len);

/**
 * Returns the type of database being used.
 *
 * @note So far: "mysql", "pgsql", "mssql", "sqlite"
 */
native dbi_type(_type[], _len);

/**
 * Returns the number of fields/colums in a result set.
 *
 * @note Unlike dbi_nextrow, you must pass a valid result handle.
 */
native dbi_num_fields(Result:result);

/**
 * Retrieves the name of a field/column in a result set.
 *
 * @note Requires a valid result handle, and columns are numbered 1 to n.
 */
native dbi_field_name(Result:result, field, name[], maxLength);

/**
 * This function can be used to find out if a table in a Sqlite database exists.
 */
stock bool:sqlite_table_exists(Sql:sql, table[]) 
{
	new bool:exists;
	new query[128];
	format(query, 127, "SELECT name FROM sqlite_master WHERE type='table' AND name='%s' LIMIT 1;", table);

	new Result:result = dbi_query(sql, query);

	if (dbi_nextrow(result))
	{
		exists = true;
	}
	else
	{
		exists = false;
	}

	if (result > RESULT_NONE)
	{
		dbi_free_result(result);
	}

	return exists;
}