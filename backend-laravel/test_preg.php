<?php
$title = 'Test/Quiz:2026';
$safeTitle = preg_replace('#[\\\\/\:\*\?\"<>\|]+#u', '_', $title);
echo "Original: $title\n";
echo "Safe: $safeTitle\n";
