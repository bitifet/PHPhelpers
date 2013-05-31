<?php

function checkVersion ( // Determine if current PHP version is equal or greather than specified.
	$minimal, // Minimal version. NOTE: all '-xxx' suffixes will be dropped.
	$current = null // Let to check against other version numbers.
) {
	is_null ($current) &&
		$current = strtolower(preg_replace ('/-.*$/', '', PHP_VERSION));
	$minimal = strtolower(preg_replace ('/-.*$/', '', $minimal));
	return version_compare ($current, $minimal) >= 0;
};

