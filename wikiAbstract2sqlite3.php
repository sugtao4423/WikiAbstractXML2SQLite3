<?php
$WIKI_XML_FILE = 'jawiki-latest-abstract.xml';
$SQLITE_FILE = 'wiki_abstract.sqlite3';
$SQLITE_TABLE_NAME = 'wikipedia';


if(file_exists($SQLITE_FILE)){
    echo "SQLite3 file exists!\nExit...\n";
    die();
}

$db = new SQLite3($SQLITE_FILE);
$db->exec("CREATE TABLE ${SQLITE_TABLE_NAME}(title TEXT, url TEXT, abstract TEXT, link_anchor TEXT, link_url TEXT)");
$db->exec('BEGIN');

$file = fopen($WIKI_XML_FILE, 'r');
while($line = fgets($file)){
    if(preg_match('/^<doc>$/', $line) === 1){
        $tmp[] = '';
        $tmpLinks = array();
    }else if(preg_match('/<title>Wikipedia: (.+)<\/title>/', $line, $m) === 1){
        $tmp['title'] = $m[1];
    }else if(preg_match('/<url>(.+)<\/url>/', $line, $m) === 1){
        $tmp['url'] = $m[1];
    }else if(preg_match('/<abstract>(.+)<\/abstract>/', $line, $m) === 1){
        $tmp['abstract'] = $m[1];
    }else if(preg_match('/<abstract \/>/', $line) === 1){
        $tmp['abstract'] = '';
    }else if(preg_match('/<sublink linktype="nav"><anchor>(.+)<\/anchor><link>(.+)<\/link><\/sublink>/', $line, $m) === 1){
        $link['anchor'] = $m[1];
        $link['link'] = $m[2];
        array_push($tmpLinks, $link);
    }else if(preg_match('/<sublink linktype="nav"><anchor \/><link>(.+)<\/link><\/sublink>/', $line, $m) === 1){
        $link['anchor'] = '';
        $link['link'] = $m[1];
        array_push($tmpLinks, $link);
    }else if(preg_match('/^<\/doc>$/', $line) === 1){
        $link_anchor = '';
        $link_url = '';
        for($i = 0; $i < count($tmpLinks); $i++){
            $link_anchor .= str_replace(',', '\\,', $tmpLinks[$i]['anchor']) . ',';
            $link_url .= str_replace(',', '\\,', $tmpLinks[$i]['link']) . ',';
        }
        $link_anchor = substr($link_anchor, 0, strlen($link_anchor) - 1);
        $link_url = substr($link_url, 0, strlen($link_url) - 1);

        $stmt = $db->prepare("INSERT INTO ${SQLITE_TABLE_NAME} VALUES (:title, :url, :abstract, :link_anchor, :link_url)");
        $stmt->bindValue(':title', $tmp['title'], SQLITE3_TEXT);
        $stmt->bindValue(':url', $tmp['url'], SQLITE3_TEXT);
        $stmt->bindValue(':abstract', $tmp['abstract'], SQLITE3_TEXT);
        $stmt->bindValue(':link_anchor', $link_anchor, SQLITE3_TEXT);
        $stmt->bindValue(':link_url', $link_url, SQLITE3_TEXT);
        $stmt->execute();
    }
}
fclose($file);

$db->exec('COMMIT');
$db->close();

