<?php
/**
 * User: Marvin Borner
 * Date: 21/09/2018
 * Time: 15:12
 */

require 'mysqlConf.inc';
require 'request.php';

$name = 'Marvin';
while (true) {
    crawl();
    $name = getFromQueue();
}

function crawl()
{
    global $name;
    if (!checkCrawled($name)) {
        print 'Crawling ' . $name . " now!\n";
        $allUsers = getData($name, 100);
        foreach ($allUsers as $user) {
            $userObject = $user['entry_data']['ProfilePage'][0]['graphql']['user'];
            $url = $userObject['profile_pic_url_hd'];
            if (!checkCompleted($url)) {
                $faceData = json_decode(exec('python3.5 FaceDetector.py ' . $url), true);
                if ((int)$faceData['count'] > 0) {
                    insertData(fixName($userObject['full_name']), $url);
                } else {
                    insertDataNoFace(fixName($userObject['full_name']), $url);
                }
                insertQueue(substr($userObject['full_name'], 0, 5));
            }
        }
        insertCrawled($name);
    }
}

function insertData($name, $url)
{
    $is_instagram = 1;
    $conn = initDbConnection();
    $stmt = $conn->prepare('INSERT IGNORE INTO humans (full_name, url, is_instagram, url_hash) VALUES (:full_name, :url, :is_instagram, :url_hash)');
    $stmt->execute([':full_name' => $name, ':url' => $url, ':is_instagram' => $is_instagram, 'url_hash' => md5($url)]);
    print 'Inserted data of ' . $name . "!\n";
}

function insertDataNoFace($name, $url)
{
    $isInstagram = 1;
    $conn = initDbConnection();
    $stmt = $conn->prepare('INSERT IGNORE INTO humans_no_face (full_name, url, is_instagram, url_hash) VALUES (:full_name, :url, :is_instagram, :url_hash)');
    $stmt->execute([':full_name' => $name, ':url' => $url, ':is_instagram' => $isInstagram, 'url_hash' => md5($url)]);
    print 'Inserted data of ' . $name . " without face!\n";
}

function insertCrawled($name)
{
    $conn = initDbConnection();
    $stmt = $conn->prepare('INSERT IGNORE INTO crawled (name) VALUES (:name)');
    $stmt->execute([':name' => $name]);
    print 'Crawling of ' . $name . " finished!\n";
}

function insertQueue($name)
{
    $conn = initDbConnection();
    $stmt = $conn->prepare('INSERT IGNORE INTO queue (name) VALUES (:name)');
    $stmt->execute([':name' => $name]);
    if ($stmt->rowCount() > 0) {
        print 'Inserted ' . $name . " into queue!\n";
    }
}

function checkCrawled($name)
{
    $conn = initDbConnection();
    $stmt = $conn->prepare('SELECT null FROM crawled WHERE name = :name');
    $stmt->execute([':name' => $name]);
    return $stmt->rowCount() !== 0;
}

function checkCompleted($url)
{
    $hash = md5($url);
    $conn = initDbConnection();
    $stmt = $conn->prepare('(SELECT null FROM humans WHERE url_hash = :hash) union (SELECT null FROM humans_no_face WHERE url_hash = :hash)');
    $stmt->execute([':hash' => $hash]);
    return $stmt->rowCount() !== 0;
}

function getFromQueue()
{
    $conn = initDbConnection();
    $selectStmt = $conn->query('SELECT * FROM queue LIMIT 1');
    $nextName = $selectStmt->fetchAll(PDO::FETCH_ASSOC)[0];
    $conn->query('DELETE FROM queue WHERE id = ' . $nextName['id']);
    return $nextName['name'];
}

function fixName($name)
{
    $name = str_replace(' ', '', $name);
    $name = preg_replace("/[^A-Za-z]/", '', $name);
    $name = implode(preg_split("/((?<=[a-z])(?=[A-Z])|(?=[A-Z][a-z]))/", $name), " ");
    $name = trim($name, '()');
    return $name;
}

function initDbConnection()
{
    global $servername, $dbname, $username, $password;
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $conn;
}