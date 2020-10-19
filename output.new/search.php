<?php
// somebody used the old entry point for external links, so redirect to the proper location
// using search.php as an entry point is depricated
header("Location: index.php?". $_SERVER['QUERY_STRING']);

die();
// don't use the rest of the file, as it is depricated
// will be erased in the future
// joschach@ap4net.at  4.5.2020
// was erased on 19.10.2020
