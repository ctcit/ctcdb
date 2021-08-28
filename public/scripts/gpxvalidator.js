
onmessage = function(e){
    var files = e.data;
    for(var i = 0; i < files.length; i++){
        var file = files[i];
        var reader = new FileReaderSync();
        var strGpx = reader.readAsBinaryString(file);
        postMessage([file.name, strGpx]);
    }
    postMessage(['finished']);
};

