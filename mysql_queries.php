<?php

	// it's a select MySQL query
	function select($conn, $table, $column, $where, $what, $order_by, $order) {
		// $conn = $conn always
		// $table = the table of the columns
		// $column = the columns of the table to list ("id, name, time") (if the column is empty, then it gives back all of the fields) (if you want to sum or count, you can just write: "category, SUM(price)")
		// $where = in wich column should be searched and how (if there is more column then it should be separated with §§ and the AND and OR should be separated from the column name with ## - column_name1§§AND##column_name2)
		// $what = what should be searched (LIKE##something, BETWEEN##something1&&something2, RELATION##mark_of_relation&&something, EXACTLY##something) (EXACTLY is only a simplier form of RELATION##=&&value) (if there is more column then it should be separated with §§)
		// $order_by = wich column should be ordered (if there is more column then it should be separated with §§)
		// $order = "ASC"/"DESC" (if there is more column then it should be separated with §§)
		// if you dont want to search or order then put there these: "" (search: $where and what, order: $order_by and $order)

		$json = array();

		// the names of the columns to select
		if ($column == "") {
			$column_names = column_names($conn, $table);
			$column = "*";
		} else {
			$column_names = preg_split("/, /", $column, NULL, PREG_SPLIT_NO_EMPTY);	
		}
		
		// the number of the columns
		$column_numbers = count($column_names);

		// should it be searched
		if ($where != "" && $what != "") {
			// check if the where and the what parameters have the same number of parts
			if (substr_count($where, "§§") == substr_count($what, "§§")) {
				// to check how many parts have the parameters
				if (strpos($where, "§§") == false && strpos($what, "§§") == false) {
					// to split the $where parameter to know what should be used - LIKE or BETWEEN or < or >
					$parts_of_parameters = preg_split("/##/", $what, NULL, PREG_SPLIT_NO_EMPTY);
					// the splited parameters
					$where_type = $parts_of_parameters[0];
					$what = $parts_of_parameters[1];

					if ($where_type == "LIKE") {
						// if $where_type is LIKE
						$where = "WHERE " . $where . " LIKE '%$what%'";
					} elseif ($where_type == "BETWEEN") {
						// if $where_type is BETWEEN then split the what once more
						$what = preg_split("/&&/", $what, NULL, PREG_SPLIT_NO_EMPTY);
						$where = "WHERE " . $where . " BETWEEN '$what[0]' AND '$what[1]'";
					} elseif ($where_type == "RELATION") {
						// if $where_type is a relation
						$what = preg_split("/&&/", $what, NULL, PREG_SPLIT_NO_EMPTY);
						$where = "WHERE " . $where . " $what[0] '$what[1]'";
					} elseif ($where_type == "EXACTLY") {
						// if $where_type is EXACTLY
						$where = "WHERE " . $where . " = '$what'";
					} else {
						$where = "";
						$what = "";
					}
				} else {
					// a temporary parameter for the query
					$where_whole = "WHERE ";
					// the parts of the where and what parameters
					$parts_of_where = preg_split("/§§/", $where, NULL, PREG_SPLIT_NO_EMPTY);
					$parts_of_what = preg_split("/§§/", $what, NULL, PREG_SPLIT_NO_EMPTY);

					for ($i = 0; $i < count($parts_of_where); $i++) {
						// to split the $what parameter to know what should be used - LIKE or BETWEEN or < or > or =
						$parts_of_what_parameter = preg_split("/##/", $parts_of_what[$i], NULL, PREG_SPLIT_NO_EMPTY);
						// to split the $where parameter to know what shoúld be used - AND or OR
						$parts_of_where_parameter = preg_split("/##/", $parts_of_where[$i], NULL, PREG_SPLIT_NO_EMPTY);
						// the splited parameters
						$where_type = $parts_of_what_parameter[0];
						$what = $parts_of_what_parameter[1];

						// create the first part (AND/OR column_name)
						if ($i == 0) {
							if (isset($parts_of_where_parameter[1])) {
								$where = $parts_of_where_parameter[1];
							} else {
								$where = $parts_of_where_parameter[0];
							}
						} else {
							$where = $parts_of_where_parameter[0] . " " . $parts_of_where_parameter[1];
						}

						// create the second part (value to search)
						if ($where_type == "LIKE") {
							// if $where_type is LIKE
							$where_whole .= $where . " LIKE '%$what%' ";
						} elseif ($where_type == "BETWEEN") {
							// if $where_type is BETWEEN then split the what once more
							$what = preg_split("/&&/", $what, NULL, PREG_SPLIT_NO_EMPTY);
							$where_whole .= $where . " BETWEEN '$what[0]' AND '$what[1]' ";
						} elseif ($where_type == "RELATION") {
							// if $where_type is a relation
							$what = preg_split("/&&/", $what, NULL, PREG_SPLIT_NO_EMPTY);
							$where_whole .= $where . " $what[0] '$what[1]' ";
						} elseif ($where_type == "EXACTLY") {
							// if $where_type is EXACTLY
							$where_whole .= $where . " = '$what' ";
						} else {
							$where_whole .= "";
							$what = "";
						}
					}

					$where = $where_whole;
				}
			} else {
				$where = "";
				$what = "";
			}
		} else {
			$where = "";
			$what = "";
		}

		// should it be ordered
		if ($order_by != "" && $order != "") {
			// check if the order_by and the order parameters have the same number of parts
			if (substr_count($order_by, "§§") == substr_count($order, "§§")) {
				// to check how many parts have the parameters
				if (strpos($order_by, "§§") == false && strpos($order, "§§") == false) {
					if ($order == "ASC" || $order == "DESC") {
						$order_by = "ORDER BY " . $order_by . " " . $order;
					} else {
						$order_by = "";
						$order = "";
					}
				} else {
					// a temporary parameter for the query
					$order_by_whole = "ORDER BY ";
					// the parts of the where and what parameters
					$parts_of_order_by = preg_split("/§§/", $order_by, NULL, PREG_SPLIT_NO_EMPTY);
					$parts_of_order = preg_split("/§§/", $order, NULL, PREG_SPLIT_NO_EMPTY);

					for ($i = 0; $i < count($parts_of_order_by); $i++) {
						if ($parts_of_order[$i] == "ASC" || $parts_of_order[$i] == "DESC") {
							$order_by_whole .= $parts_of_order_by[$i] . " " . $parts_of_order[$i];
						} else {
							$order_by_whole .= "";
							$order = "";
						}
						// check if we need a comma at the end of the $order_by_whole
						if ($i != count($parts_of_order_by) - 1 && $order_by_whole[count($order_by_whole) - 1] != ",") {
							$order_by_whole .= ", ";
						}
					}

					$order_by = $order_by_whole;
				}
			} else {
				$order_by = "";
				$order = "";
			}
		} else {
			$order_by = "";
			$order = "";
		}

		// // if mysql native driver is installed, you can use this code
		// $stmt = $conn->prepare("SELECT $column FROM $table $where $order_by $order");
		// $stmt->execute();
		// $result = $stmt->get_result();
		// while ($row = $result->fetch_assoc()) {
		// 	// for ($i = 0; $i < $column_numbers; $i++) {
		// 	// 	echo $row[$column_names[$i]];
		// 	// 	echo "<br/>";

		// 	// }
		// 	array_push($json, $row);
		// }

		// echo "SELECT $column FROM $table $where $order_by";

		// if mysql native driver is not installed, you have to use this code
		$stmt = $conn->prepare("SELECT $column FROM $table $where $order_by");
		$stmt->execute();
		// create the array for bind_result
		for ($i=0;$i<$column_numbers;$i++) { 
		    $var = $i;
		    $$var = null; 
		    $data[$var] = &$$var; 
		}
		// bind the results to the array
		call_user_func_array(array($stmt,'bind_result'), $data);
		// create the response array
		while ($stmt->fetch()) {
			$row = array();
			for ($i = 0; $i < $column_numbers; $i++) {
				$row[$column_names[$i]] = $data[$i];
			}
			array_push($json, $row);
		}
		

		$stmt->close();

		$encoded_json = json_encode($json);
		$decoded_json = json_decode($encoded_json);
		
		return $decoded_json;

		// echo $encoded_json;
		// echo $decoded_json[0]->article_id;
	}



	// it"s an update MySQL query
	function update($conn, $table, $column, $row, $row_value, $new_value) {
		// $conn = $conn always
		// $table = the table of the columns
		// $column = the columns of the table to update	
		// $row = the column of the table in wich we wanna define the row
		// $row_value = the value of the row in wich the cell wanna be updated
		// $new_value = the new value of the cell

		// the type of the column to update
		$column_type = column_type($conn, $table, $column);

		$stmt = $conn->prepare("UPDATE $table SET $column = ? WHERE $row = '$row_value'");
		$stmt->bind_param($column_type, $new_value);
		$stmt->execute();
		$stmt->close();
	}



	// it"s an insert into MySQL query
	function insert($conn, $table, $new_values) {
		// $conn = $conn always
		// $table = the table of the columns	
		// $new_value = the value of the cells (all of the fields have to get a value)

		// the names of the columns to select
		$column_names = column_names($conn, $table);
		// the number of the columns
		$column_numbers = count($column_names);

		// the "?" to the sql query
		$values = "";
		// the types of the columns
		$column_types = "";
		for ($i = 0; $i < $column_numbers; $i++) {
			if ($i < $column_numbers - 1) {
				$values .= "?, ";
			} else {
				$values .= "?";
			}
			$column_types .= column_type($conn, $table, $column_names[$i]);
		}

		// create array from $new_values parameter
		$new_values = preg_split("/, /", $new_values, NULL, PREG_SPLIT_NO_EMPTY);

		// create array to the bind_param
		$cell_values = array();
		$cell_values[] = & $column_types;
		for ($i = 0; $i < $column_numbers; $i++) {
			$cell_values[] = & $new_values[$i];
		}

		$stmt = $conn->prepare("INSERT INTO $table VALUES($values)");
		call_user_func_array(array($stmt, 'bind_param'), $cell_values);
		$stmt->execute();
		$stmt->close();
	}



	// delete a row from a table
	function delete($conn, $table, $column, $row_value) {
		// $conn = $conn always
		// $table = the table of the row
		// $column = the columns of the table in wich we give the $row_value to delete the row of that value
		// $row_value = the value of the row wich wanna be deleted

		$column_type = column_type($conn, $table, $column);

		$stmt = $conn->prepare("DELETE FROM $table WHERE $column = ?");
		$stmt->bind_param($column_type, $row_value);
		$stmt->execute();
		$stmt->close();
	}



	// it gives back the type of a column
	function column_type($conn, $table, $column) {
		// $conn = $conn always
		// $table = the table of the columns
		// $column = the columns of the table to define type

		$stmt = $conn->prepare("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$column'");
		$stmt->execute();
		$stmt->bind_result($column_type);	
		$stmt->fetch();
		$stmt->close();

		// the type of the column without the max. char. numb.
		$column_type = preg_split("/\(/", $column_type, NULL, PREG_SPLIT_NO_EMPTY)[0];

		// the letter of the type of the column
		if ($column_type == "int") {
			$column_type = "i";
		} elseif ($column_type == "decimal") {
			$column_type = "d";
		} else {
			$column_type = "s";
		}

		return $column_type;
	}



	// it gives back the names of the columns of a table
	function column_names($conn, $table) {
		// $conn = $conn always
		// $table = the table of the columns

		$column_names = array();

		$stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table'");
		$stmt->execute();
		$stmt->bind_result($column_name);
		while ($stmt->fetch()) {
			array_push($column_names, $column_name);
		}
		$stmt->close();

		return $column_names;
	}

?>