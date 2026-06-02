<?php
$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
$isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|tablet|playbook|silk/i', $userAgent);

if ($isMobile) {
    include "menu-bottom.php";
} else {
    include "menu-aside.php";
}
?>