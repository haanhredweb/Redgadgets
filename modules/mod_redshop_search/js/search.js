function getHTTPObject() {
    var xhr = false;
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        try {
            xhr = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e) {
            try {
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (e) {
                xhr = false;
            }
        }
    }
    return xhr;
}
function setSearchType() {
    document.getElementById('defaultSearchType').value = document.getElementById('search_type').value;
}

function loadProducts(tid, mid) {
    request = getHTTPObject();
    request.onreadystatechange = sendProductData;
    request.open("GET", base_url + "index.php?tmpl=component&option=com_redshop&view=search&task=loadProducts&taskid=" + tid + "&manufacture_id=" + mid, true);
    request.send(null);
}
// function is executed when var request state changes
function sendProductData() {
    // if request object received response
    if (request.readyState == 4) {
        var reponce = request.responseText;
        if (reponce != "" && document.getElementById('product_search_catdata_product')) {

            var resdiv = document.getElementById('product_search_catdata_product');
            resdiv.style.display = 'block';
            resdiv.innerHTML = reponce;
        }
    }

}
 