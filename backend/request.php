<?php
/**
 * User: Marvin Borner
 * Date: 21/09/2018
 * Time: 15:12
 */

function getData($fullName, $count)
{
    $searchData = makeRequest('https://www.instagram.com/web/search/topsearch/?query=' . $fullName);
    $userContentJson = [];
    $usernames = array_map(function ($arr) {
        return $arr['user']['username'];
    }, json_decode($searchData[0], true)['users']);

    for ($i = 0; $i < $count ?? 100; $i++) {
        if (!isset($usernames[$i])) {
            break;
        }
        $userData = makeRequest('https://instagram.com/' . $usernames[$i] . '/');
        $userDom = createPathFromHtml($userData[0]);

        // find script tag
        foreach ($userDom->query('//script') as $scriptTag) {
            $scriptTagContent = $scriptTag->textContent;
            if (strpos($scriptTagContent, 'window._sharedData = ') === 0) {
                $userContentJson[] = json_decode(substr(substr($scriptTagContent, 21), 0, -1), true);
                break;
            }
        }
    }

    return $userContentJson;
}

function processData($userData)
{
    foreach ($userData as $user) {
        $userObject = $user['entry_data']['ProfilePage'][0]['graphql']['user'];
        $faceData = json_decode(exec('python3.5 FaceDetector.py ' . $userObject['profile_pic_url_hd']), true);
        if ((int)$faceData['count'] > 0) {
            $base64string = 'data:image/jpeg;base64,';
            print '<img onmouseover="replaceImageSource(this)" onmouseout="replaceImageSource(this)" title="' . $userObject['full_name'] . '" src="' . $userObject['profile_pic_url_hd'] . '" data-src="' . $base64string . $faceData['feature'] . '" />';
        }
    }
}

function createPathFromHtml($content)
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($content);
    libxml_use_internal_errors(false);
    return new DOMXPath($dom);
}

function makeRequest($url)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
    $content = curl_exec($curl);
    $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $downloadSize = curl_getinfo($curl, CURLINFO_SIZE_DOWNLOAD) / 1000 . "KB\n";
    $updatedUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL); // update on 301/302
    curl_close($curl);

    return [$content, $responseCode, $downloadSize, $updatedUrl];
}
