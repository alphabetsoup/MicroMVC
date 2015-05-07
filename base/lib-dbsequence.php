<?php

class DBSequence {
	function create($key, $start, $db, $table) {
		/* If it already exists just fail */
		$res = $db->query(sprintf("INSERT INTO %s (seqKey, nextId) VALUES (%s, %s)", 
			$db->quoteIdentifier($table),
			$db->quoteSmart($key), 
			$db->quoteSmart($start))
		);
		return $res;
	}

	function nextId($key, $db, $table) {
		$db->query("LOCK TABLE `$table` WRITE");
		$sql = sprintf("SELECT nextId+1 FROM %s WHERE seqKey = %s", 
			$db->quoteIdentifier($table),
			$db->quoteSmart($key)
		);
		$nextId = $db->getOne($sql);
		
		if($nextId == null) {
			DBSequence::create($key, 15000, $db, $table);
			$nextId = $db->getOne($sql);
		}
		
		$update = sprintf("UPDATE %s SET nextId = nextId+1 WHERE seqKey = %s", 
			$db->quoteIdentifier($table),
			$db->quoteSmart($key)
		);
		$res = $db->query($update);
		
		if(DB::isError($res)) {
			$nextId = $res;
		}
		$db->query("UNLOCK TABLES");
		return intval($nextId);
	}
}
