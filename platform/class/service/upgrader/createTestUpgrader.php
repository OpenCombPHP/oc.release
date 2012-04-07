<?php
namespace org\opencomb\platform\system\upgrader ;

/**
 * 这个文件单纯用于产生测试文件
 */
$sFrom = $_SERVER['argv'][1];
$sTo = $_SERVER['argv'][2];
$sFileName = "upgrader_${sFrom}To$sTo.php";
file_put_contents($sFileName, <<<CODE
<?php
namespace org\opencomb\platform\system\upgrader ;

class upgrader_${sFrom}To$sTo extends AbstractTestUpgrader{
}

CODE
);
