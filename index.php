<?php
    if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
        // windows
        pclose(popen("start /b php src\CLI\listener.php", "w"));  
    }else{
        // linux
        exec("nohup php C:\xampp\htdocs\Test\src\CLI\listener.php &");
    }
?>

<html>
<head>
<title>test</title>
<script>

function connection(){
    var canvas =  document.getElementById("canvas");
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = "black";
    ctx.lineWidth = 1;

    var fpsIntervalId;
    var mesCount = 0;
    var drawCount = 0;

    var connection;
    var DrawObjects = {};

    try{
        connection = new WebSocket('ws://'+window.location.hostname+':8080');
    }catch(e){
        console.log(e);
        return;
    }

    var recieveDataSize = 0;
    var totalReciveDataSize = 0;

    connection.onopen = function(e) {
        document.getElementById("connection").disabled= true;
        console.log("Connection established!");

        connection.send(JSON.stringify({"app":"start","width":canvas.width,"height":canvas.height}));

        // FPS測定
        var preTime_fps=(new Date).getTime();
        
        var fpsElem = document.getElementById("FPS");
        var datasizeElem = document.getElementById("DataSize");
        fpsIntervalId = setInterval(function(){
            totalReciveDataSize += recieveDataSize;
            var now = (new Date).getTime();
            var div = now - preTime_fps;
            if(div >= 1000){
                fpsElem.textContent = "Message/Sec:" + parseInt(1000 / (div / mesCount ))
                                        + ", Frame/Sec:" + parseInt(1000 / (div / drawCount ));
                
                datasizeElem.textContent =  "Total:" + (function(){
                                                var unit = ["B","KB","MB","GB"];
                                                var upperUnit = totalReciveDataSize;
                                                for(var i=0; i<unit.length-1; i++){
                                                    if(upperUnit < 1024){
                                                        return Math.round(upperUnit * 1000) / 1000 + unit[i];
                                                    }
                                                    upperUnit = upperUnit / 1024;
                                                }
                                                return Math.round(upperUnit * 1000) / 1000 + unit[unit.length -1];
                                            })()
                                        + ", "+ parseInt(1000 / (div / recieveDataSize )) + " Byte/Sec:";
                recieveDataSize = 0;
                mesCount = 0;
                drawCount = 0;
                preTime_fps = now;
            }
            if(connection.readyState == 2 || connection.readyState == 3){
                // closeing and closed
                clearInterval(fpsIntervalId);
            }
        },1000);


        // 描画
        canvasIntervalId = setInterval(function(){
            if(connection.readyState == 2 || connection.readyState == 3){
                // closeing and closed
                clearInterval(canvasIntervalId);
            }
            if(DrawObjects == null) return;

            ctx.clearRect(0,0,canvas.width,canvas.height);
            Object.keys(DrawObjects).forEach(function(key){
                var objValue = DrawObjects[key];
                var deltaTime = new Date().getTime()/1000 - objValue.timestamp;
                switch(objValue["view"]){
                    case "PlayerBullet":
                        var pos = {"x":objValue.pos.x + objValue.velocity.x * deltaTime, "y":objValue.pos.y + objValue.velocity.y * deltaTime};
                        ctx.beginPath();
                        ctx.arc(pos.x, pos.y, 3, 0, Math.PI*2, true);
                        ctx.stroke();
                        ctx.closePath();
                        break;
                    case "Player":
                        var preFillStyle = ctx.fillStyle;
                        var pos = {"x":objValue.pos.x + objValue.velocity.x * deltaTime, "y":objValue.pos.y + objValue.velocity.y * deltaTime};
                        ctx.beginPath();
                        ctx.arc(pos.x, pos.y, 10, 0, Math.PI*2, true);
                        ctx.fill();
                        ctx.fillStyle = "red";
                        ctx.beginPath();
                        ctx.arc(pos.x, pos.y, 5, 0, Math.PI*2, true);
                        ctx.fill();
                        ctx.fillStyle = preFillStyle;
                        break;
                    case "Enemy":
                        var pos = {"x":objValue.pos.x + objValue.velocity.x * deltaTime, "y":objValue.pos.y + objValue.velocity.y * deltaTime};
                        ctx.beginPath();
                        ctx.arc(pos.x, pos.y, 10, 0, Math.PI*2, true);
                        ctx.fill();
                        break;
                    case "Polygon":
                        ctx.beginPath();
                        var firstPoint = objValue.polygon[0];
                        var pos = {"x":objValue.pos.x + objValue.velocity.x * deltaTime, "y":objValue.pos.y + objValue.velocity.y * deltaTime};
                        ctx.moveTo(pos.x + firstPoint.x, pos.y + firstPoint.y);
                        objValue.polygon.forEach(function(v){
                            ctx.lineTo(pos.x + v.x, pos.y + v.y);
                        })
                        ctx.lineTo(pos.x + firstPoint.x, pos.y +firstPoint.y);
                        ctx.stroke();
                        ctx.closePath();
                        break;
                    default:
                }
            })
            drawCount++;
        },16);
        // 1000(mSec) / 60(Frame) = 16.66...

        var keydownListener = function(ev){
            if(connection.readyState == 1){
                var sendData = {"app":"keydown", "key":ev.keyCode,};
                if(ev.repeat == false ){
                    connection.send(JSON.stringify(sendData));
                }
            }else if(connection.readyState == 2 || connection.readyState == 3){
                // closeing and closed
                window.removeEventListener("keydown", keydownListener);
            }
        }
        window.addEventListener("keydown", keydownListener);
        var keyupListener = function(ev){
            if(connection.readyState == 1){
                var sendData = {"app":"keyup", "key":ev.keyCode,};
                if(ev.repeat == false ){
                    connection.send(JSON.stringify(sendData));
                }
            }else if(connection.readyState == 2 || connection.readyState == 3){
                // closeing and closed
                window.removeEventListener("keyup", keyupListener);
            }
        }
        window.addEventListener("keyup", keyupListener);
    };

    connection.onerror = function(e){
        console.log(e);
        document.getElementById("connection").disabled= false;
    }

    connection.onmessage = function(e) {
        recieveDataSize += getStringByteSize(e.data);
        var jsonObj = JSON.parse(e.data);
        Object.assign(DrawObjects, jsonObj.update);
        jsonObj.remove.forEach(function(tag){
            delete DrawObjects[tag];
        })
        // FPS測定
        mesCount++;
        function getStringByteSize(string) {
            string = string ? string : "";
            return(encodeURIComponent(string).replace(/%../g,"x").length);
        }
    };

    connection.onclose = function(e) {
        console.log(e.data);
        document.getElementById("connection").disabled= false;
    };
}
window.onload=function(){
    var canvas = document.getElementById("canvas");
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = canvas.parentElement.clientHeight;
}

</script>
</head>
<body>
<button id="connection" onclick="connection()">START</button>
<span id="FPS"></span>
<span id="DataSize"></span>
<div style="border:1px solid black;height:400px;width:600px;margin-top:1em;margin:auto">
    <canvas id="canvas"></canvas>
</div>
</body>
</html>