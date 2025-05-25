<?php
function humanReadableDate($datetime) {
    $date = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($date)->days;

    if ($diff == 0) return 'Today';
    if ($diff == 1) return 'Yesterday';
    return $date->format('F j, Y');
}
?>
