function searchHuman() {
    const name = document.getElementById("nameInput").value;
    const count = document.getElementById("countInput").value;
    httpGet("../backend/request.php?name=" + name + "&count=" + count, response => {
        let list = document.getElementById('list');
        list.innerHTML += response;
    });
}

function httpGet(url, callback) {
    const xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function () {
        if (xmlHttp.readyState === 4 && xmlHttp.status === 200)
            callback(xmlHttp.responseText);
    };
    xmlHttp.open("GET", url, true);
    xmlHttp.send(null);
}