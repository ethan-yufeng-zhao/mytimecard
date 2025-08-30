<?php
const HTTP_BASEURL = 'http://192.168.10.88:80/training-cert-system/';

echo('<html>');
echo('<head>');
echo('<title>');
echo('This is a test page for JSON API.');
echo('</title>');
echo('</head>');

echo('<body>');
echo('<table border=2>');
echo('<tr>');
echo('<th>');
echo('No.');
echo('</th>');

echo('<th>');
echo('API');
echo('</th>');
echo('</tr>');

echo('<tr>');
echo('<td>');
echo('#1');
echo('</td>');

echo('<td>');
echo('<a href="'.HTTP_BASEURL.'JSON/JSON_template_info_by_template_id.php?template_id=50">');
echo('JSON/JSON_template_info_by_template_id.php?template_id=50');
echo('</a>');
echo('</td>');
echo('</tr>');

echo('</table>');

echo('</body>');
echo('</html>');
