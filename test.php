<!DOCTYPE html>
<html>
<body>

<?php
$string = '\x3Cstyle\x3E\x0A\x20\x20\x20.mainDiv';

// \\ convert the octal into string
$string = preg_replace_callback('/\\\\x([0-9A-F]{1,2})/i', function ($m) {
    return chr(hexdec($m[1]));
}, $string);
echo $string;
?> 
 
</body>
</html>
