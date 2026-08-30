/*!
 * Paytaro QR Widget v1.0.0 —— 面板弹窗显码组件（无收银台模式）
 * 用法：PaytaroQR.render(container, { order, payment, status:{url, method?, body?, interval?, parse?}, mode:'inline'|'modal'|'page', onPaid, onExpired, onClose, texts })
 * 无外部依赖；内置二维码生成器 qrcode-generator (c) Kazuhiko Arase, MIT License。
 * 文档：https://v3.paytaro.com/#/docs/install-script
 */
(function (global) {
  'use strict';
  /* ---- qrcode-generator 1.4.4 (minified, MIT) ---- */
var qrcode=function(){var t=function(t,r){var e=t,n=g[r],o=null,i=0,a=null,u=[],f={},c=function(t,r){o=function(t){for(var r=new Array(t),e=0;e<t;e+=1){r[e]=new Array(t);for(var n=0;n<t;n+=1)r[e][n]=null}return r}(i=4*e+17),l(0,0),l(i-7,0),l(0,i-7),s(),h(),d(t,r),e>=7&&v(t),null==a&&(a=p(e,n,u)),w(a,r)},l=function(t,r){for(var e=-1;e<=7;e+=1)if(!(t+e<=-1||i<=t+e))for(var n=-1;n<=7;n+=1)r+n<=-1||i<=r+n||(o[t+e][r+n]=0<=e&&e<=6&&(0==n||6==n)||0<=n&&n<=6&&(0==e||6==e)||2<=e&&e<=4&&2<=n&&n<=4)},h=function(){for(var t=8;t<i-8;t+=1)null==o[t][6]&&(o[t][6]=t%2==0);for(var r=8;r<i-8;r+=1)null==o[6][r]&&(o[6][r]=r%2==0)},s=function(){for(var t=B.getPatternPosition(e),r=0;r<t.length;r+=1)for(var n=0;n<t.length;n+=1){var i=t[r],a=t[n];if(null==o[i][a])for(var u=-2;u<=2;u+=1)for(var f=-2;f<=2;f+=1)o[i+u][a+f]=-2==u||2==u||-2==f||2==f||0==u&&0==f}},v=function(t){for(var r=B.getBCHTypeNumber(e),n=0;n<18;n+=1){var a=!t&&1==(r>>n&1);o[Math.floor(n/3)][n%3+i-8-3]=a}for(n=0;n<18;n+=1){a=!t&&1==(r>>n&1);o[n%3+i-8-3][Math.floor(n/3)]=a}},d=function(t,r){for(var e=n<<3|r,a=B.getBCHTypeInfo(e),u=0;u<15;u+=1){var f=!t&&1==(a>>u&1);u<6?o[u][8]=f:u<8?o[u+1][8]=f:o[i-15+u][8]=f}for(u=0;u<15;u+=1){f=!t&&1==(a>>u&1);u<8?o[8][i-u-1]=f:u<9?o[8][15-u-1+1]=f:o[8][15-u-1]=f}o[i-8][8]=!t},w=function(t,r){for(var e=-1,n=i-1,a=7,u=0,f=B.getMaskFunction(r),c=i-1;c>0;c-=2)for(6==c&&(c-=1);;){for(var g=0;g<2;g+=1)if(null==o[n][c-g]){var l=!1;u<t.length&&(l=1==(t[u]>>>a&1)),f(n,c-g)&&(l=!l),o[n][c-g]=l,-1==(a-=1)&&(u+=1,a=7)}if((n+=e)<0||i<=n){n-=e,e=-e;break}}},p=function(t,r,e){for(var n=A.getRSBlocks(t,r),o=b(),i=0;i<e.length;i+=1){var a=e[i];o.put(a.getMode(),4),o.put(a.getLength(),B.getLengthInBits(a.getMode(),t)),a.write(o)}var u=0;for(i=0;i<n.length;i+=1)u+=n[i].dataCount;if(o.getLengthInBits()>8*u)throw"code length overflow. ("+o.getLengthInBits()+">"+8*u+")";for(o.getLengthInBits()+4<=8*u&&o.put(0,4);o.getLengthInBits()%8!=0;)o.putBit(!1);for(;!(o.getLengthInBits()>=8*u||(o.put(236,8),o.getLengthInBits()>=8*u));)o.put(17,8);return function(t,r){for(var e=0,n=0,o=0,i=new Array(r.length),a=new Array(r.length),u=0;u<r.length;u+=1){var f=r[u].dataCount,c=r[u].totalCount-f;n=Math.max(n,f),o=Math.max(o,c),i[u]=new Array(f);for(var g=0;g<i[u].length;g+=1)i[u][g]=255&t.getBuffer()[g+e];e+=f;var l=B.getErrorCorrectPolynomial(c),h=k(i[u],l.getLength()-1).mod(l);for(a[u]=new Array(l.getLength()-1),g=0;g<a[u].length;g+=1){var s=g+h.getLength()-a[u].length;a[u][g]=s>=0?h.getAt(s):0}}var v=0;for(g=0;g<r.length;g+=1)v+=r[g].totalCount;var d=new Array(v),w=0;for(g=0;g<n;g+=1)for(u=0;u<r.length;u+=1)g<i[u].length&&(d[w]=i[u][g],w+=1);for(g=0;g<o;g+=1)for(u=0;u<r.length;u+=1)g<a[u].length&&(d[w]=a[u][g],w+=1);return d}(o,n)};f.addData=function(t,r){var e=null;switch(r=r||"Byte"){case"Numeric":e=M(t);break;case"Alphanumeric":e=x(t);break;case"Byte":e=m(t);break;case"Kanji":e=L(t);break;default:throw"mode:"+r}u.push(e),a=null},f.isDark=function(t,r){if(t<0||i<=t||r<0||i<=r)throw t+","+r;return o[t][r]},f.getModuleCount=function(){return i},f.make=function(){if(e<1){for(var t=1;t<40;t++){for(var r=A.getRSBlocks(t,n),o=b(),i=0;i<u.length;i++){var a=u[i];o.put(a.getMode(),4),o.put(a.getLength(),B.getLengthInBits(a.getMode(),t)),a.write(o)}var g=0;for(i=0;i<r.length;i++)g+=r[i].dataCount;if(o.getLengthInBits()<=8*g)break}e=t}c(!1,function(){for(var t=0,r=0,e=0;e<8;e+=1){c(!0,e);var n=B.getLostPoint(f);(0==e||t>n)&&(t=n,r=e)}return r}())},f.createTableTag=function(t,r){t=t||2;var e="";e+='<table style="',e+=" border-width: 0px; border-style: none;",e+=" border-collapse: collapse;",e+=" padding: 0px; margin: "+(r=void 0===r?4*t:r)+"px;",e+='">',e+="<tbody>";for(var n=0;n<f.getModuleCount();n+=1){e+="<tr>";for(var o=0;o<f.getModuleCount();o+=1)e+='<td style="',e+=" border-width: 0px; border-style: none;",e+=" border-collapse: collapse;",e+=" padding: 0px; margin: 0px;",e+=" width: "+t+"px;",e+=" height: "+t+"px;",e+=" background-color: ",e+=f.isDark(n,o)?"#000000":"#ffffff",e+=";",e+='"/>';e+="</tr>"}return e+="</tbody>",e+="</table>"},f.createSvgTag=function(t,r,e,n){var o={};"object"==typeof arguments[0]&&(t=(o=arguments[0]).cellSize,r=o.margin,e=o.alt,n=o.title),t=t||2,r=void 0===r?4*t:r,(e="string"==typeof e?{text:e}:e||{}).text=e.text||null,e.id=e.text?e.id||"qrcode-description":null,(n="string"==typeof n?{text:n}:n||{}).text=n.text||null,n.id=n.text?n.id||"qrcode-title":null;var i,a,u,c,g=f.getModuleCount()*t+2*r,l="";for(c="l"+t+",0 0,"+t+" -"+t+",0 0,-"+t+"z ",l+='<svg version="1.1" xmlns="http://www.w3.org/2000/svg"',l+=o.scalable?"":' width="'+g+'px" height="'+g+'px"',l+=' viewBox="0 0 '+g+" "+g+'" ',l+=' preserveAspectRatio="xMinYMin meet"',l+=n.text||e.text?' role="img" aria-labelledby="'+y([n.id,e.id].join(" ").trim())+'"':"",l+=">",l+=n.text?'<title id="'+y(n.id)+'">'+y(n.text)+"</title>":"",l+=e.text?'<description id="'+y(e.id)+'">'+y(e.text)+"</description>":"",l+='<rect width="100%" height="100%" fill="white" cx="0" cy="0"/>',l+='<path d="',a=0;a<f.getModuleCount();a+=1)for(u=a*t+r,i=0;i<f.getModuleCount();i+=1)f.isDark(a,i)&&(l+="M"+(i*t+r)+","+u+c);return l+='" stroke="transparent" fill="black"/>',l+="</svg>"},f.createDataURL=function(t,r){t=t||2,r=void 0===r?4*t:r;var e=f.getModuleCount()*t+2*r,n=r,o=e-r;return I(e,e,function(r,e){if(n<=r&&r<o&&n<=e&&e<o){var i=Math.floor((r-n)/t),a=Math.floor((e-n)/t);return f.isDark(a,i)?0:1}return 1})},f.createImgTag=function(t,r,e){t=t||2,r=void 0===r?4*t:r;var n=f.getModuleCount()*t+2*r,o="";return o+="<img",o+=' src="',o+=f.createDataURL(t,r),o+='"',o+=' width="',o+=n,o+='"',o+=' height="',o+=n,o+='"',e&&(o+=' alt="',o+=y(e),o+='"'),o+="/>"};var y=function(t){for(var r="",e=0;e<t.length;e+=1){var n=t.charAt(e);switch(n){case"<":r+="&lt;";break;case">":r+="&gt;";break;case"&":r+="&amp;";break;case'"':r+="&quot;";break;default:r+=n}}return r};return f.createASCII=function(t,r){if((t=t||1)<2)return function(t){t=void 0===t?2:t;var r,e,n,o,i,a=1*f.getModuleCount()+2*t,u=t,c=a-t,g={"██":"█","█ ":"▀"," █":"▄","  ":" "},l={"██":"▀","█ ":"▀"," █":" ","  ":" "},h="";for(r=0;r<a;r+=2){for(n=Math.floor((r-u)/1),o=Math.floor((r+1-u)/1),e=0;e<a;e+=1)i="█",u<=e&&e<c&&u<=r&&r<c&&f.isDark(n,Math.floor((e-u)/1))&&(i=" "),u<=e&&e<c&&u<=r+1&&r+1<c&&f.isDark(o,Math.floor((e-u)/1))?i+=" ":i+="█",h+=t<1&&r+1>=c?l[i]:g[i];h+="\n"}return a%2&&t>0?h.substring(0,h.length-a-1)+Array(a+1).join("▀"):h.substring(0,h.length-1)}(r);t-=1,r=void 0===r?2*t:r;var e,n,o,i,a=f.getModuleCount()*t+2*r,u=r,c=a-r,g=Array(t+1).join("██"),l=Array(t+1).join("  "),h="",s="";for(e=0;e<a;e+=1){for(o=Math.floor((e-u)/t),s="",n=0;n<a;n+=1)i=1,u<=n&&n<c&&u<=e&&e<c&&f.isDark(o,Math.floor((n-u)/t))&&(i=0),s+=i?g:l;for(o=0;o<t;o+=1)h+=s+"\n"}return h.substring(0,h.length-1)},f.renderTo2dContext=function(t,r){r=r||2;for(var e=f.getModuleCount(),n=0;n<e;n++)for(var o=0;o<e;o++)t.fillStyle=f.isDark(n,o)?"black":"white",t.fillRect(n*r,o*r,r,r)},f};t.stringToBytes=(t.stringToBytesFuncs={default:function(t){for(var r=[],e=0;e<t.length;e+=1){var n=t.charCodeAt(e);r.push(255&n)}return r}}).default,t.createStringToBytes=function(t,r){var e=function(){for(var e=S(t),n=function(){var t=e.read();if(-1==t)throw"eof";return t},o=0,i={};;){var a=e.read();if(-1==a)break;var u=n(),f=n()<<8|n();i[String.fromCharCode(a<<8|u)]=f,o+=1}if(o!=r)throw o+" != "+r;return i}(),n="?".charCodeAt(0);return function(t){for(var r=[],o=0;o<t.length;o+=1){var i=t.charCodeAt(o);if(i<128)r.push(i);else{var a=e[t.charAt(o)];"number"==typeof a?(255&a)==a?r.push(a):(r.push(a>>>8),r.push(255&a)):r.push(n)}}return r}};var r,e,n,o,i,a=1,u=2,f=4,c=8,g={L:1,M:0,Q:3,H:2},l=0,h=1,s=2,v=3,d=4,w=5,p=6,y=7,B=(r=[[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42],[6,26,46],[6,28,50],[6,30,54],[6,32,58],[6,34,62],[6,26,46,66],[6,26,48,70],[6,26,50,74],[6,30,54,78],[6,30,56,82],[6,30,58,86],[6,34,62,90],[6,28,50,72,94],[6,26,50,74,98],[6,30,54,78,102],[6,28,54,80,106],[6,32,58,84,110],[6,30,58,86,114],[6,34,62,90,118],[6,26,50,74,98,122],[6,30,54,78,102,126],[6,26,52,78,104,130],[6,30,56,82,108,134],[6,34,60,86,112,138],[6,30,58,86,114,142],[6,34,62,90,118,146],[6,30,54,78,102,126,150],[6,24,50,76,102,128,154],[6,28,54,80,106,132,158],[6,32,58,84,110,136,162],[6,26,54,82,110,138,166],[6,30,58,86,114,142,170]],e=1335,n=7973,i=function(t){for(var r=0;0!=t;)r+=1,t>>>=1;return r},(o={}).getBCHTypeInfo=function(t){for(var r=t<<10;i(r)-i(e)>=0;)r^=e<<i(r)-i(e);return 21522^(t<<10|r)},o.getBCHTypeNumber=function(t){for(var r=t<<12;i(r)-i(n)>=0;)r^=n<<i(r)-i(n);return t<<12|r},o.getPatternPosition=function(t){return r[t-1]},o.getMaskFunction=function(t){switch(t){case l:return function(t,r){return(t+r)%2==0};case h:return function(t,r){return t%2==0};case s:return function(t,r){return r%3==0};case v:return function(t,r){return(t+r)%3==0};case d:return function(t,r){return(Math.floor(t/2)+Math.floor(r/3))%2==0};case w:return function(t,r){return t*r%2+t*r%3==0};case p:return function(t,r){return(t*r%2+t*r%3)%2==0};case y:return function(t,r){return(t*r%3+(t+r)%2)%2==0};default:throw"bad maskPattern:"+t}},o.getErrorCorrectPolynomial=function(t){for(var r=k([1],0),e=0;e<t;e+=1)r=r.multiply(k([1,C.gexp(e)],0));return r},o.getLengthInBits=function(t,r){if(1<=r&&r<10)switch(t){case a:return 10;case u:return 9;case f:case c:return 8;default:throw"mode:"+t}else if(r<27)switch(t){case a:return 12;case u:return 11;case f:return 16;case c:return 10;default:throw"mode:"+t}else{if(!(r<41))throw"type:"+r;switch(t){case a:return 14;case u:return 13;case f:return 16;case c:return 12;default:throw"mode:"+t}}},o.getLostPoint=function(t){for(var r=t.getModuleCount(),e=0,n=0;n<r;n+=1)for(var o=0;o<r;o+=1){for(var i=0,a=t.isDark(n,o),u=-1;u<=1;u+=1)if(!(n+u<0||r<=n+u))for(var f=-1;f<=1;f+=1)o+f<0||r<=o+f||0==u&&0==f||a==t.isDark(n+u,o+f)&&(i+=1);i>5&&(e+=3+i-5)}for(n=0;n<r-1;n+=1)for(o=0;o<r-1;o+=1){var c=0;t.isDark(n,o)&&(c+=1),t.isDark(n+1,o)&&(c+=1),t.isDark(n,o+1)&&(c+=1),t.isDark(n+1,o+1)&&(c+=1),0!=c&&4!=c||(e+=3)}for(n=0;n<r;n+=1)for(o=0;o<r-6;o+=1)t.isDark(n,o)&&!t.isDark(n,o+1)&&t.isDark(n,o+2)&&t.isDark(n,o+3)&&t.isDark(n,o+4)&&!t.isDark(n,o+5)&&t.isDark(n,o+6)&&(e+=40);for(o=0;o<r;o+=1)for(n=0;n<r-6;n+=1)t.isDark(n,o)&&!t.isDark(n+1,o)&&t.isDark(n+2,o)&&t.isDark(n+3,o)&&t.isDark(n+4,o)&&!t.isDark(n+5,o)&&t.isDark(n+6,o)&&(e+=40);var g=0;for(o=0;o<r;o+=1)for(n=0;n<r;n+=1)t.isDark(n,o)&&(g+=1);return e+=Math.abs(100*g/r/r-50)/5*10},o),C=function(){for(var t=new Array(256),r=new Array(256),e=0;e<8;e+=1)t[e]=1<<e;for(e=8;e<256;e+=1)t[e]=t[e-4]^t[e-5]^t[e-6]^t[e-8];for(e=0;e<255;e+=1)r[t[e]]=e;var n={glog:function(t){if(t<1)throw"glog("+t+")";return r[t]},gexp:function(r){for(;r<0;)r+=255;for(;r>=256;)r-=255;return t[r]}};return n}();function k(t,r){if(void 0===t.length)throw t.length+"/"+r;var e=function(){for(var e=0;e<t.length&&0==t[e];)e+=1;for(var n=new Array(t.length-e+r),o=0;o<t.length-e;o+=1)n[o]=t[o+e];return n}(),n={getAt:function(t){return e[t]},getLength:function(){return e.length},multiply:function(t){for(var r=new Array(n.getLength()+t.getLength()-1),e=0;e<n.getLength();e+=1)for(var o=0;o<t.getLength();o+=1)r[e+o]^=C.gexp(C.glog(n.getAt(e))+C.glog(t.getAt(o)));return k(r,0)},mod:function(t){if(n.getLength()-t.getLength()<0)return n;for(var r=C.glog(n.getAt(0))-C.glog(t.getAt(0)),e=new Array(n.getLength()),o=0;o<n.getLength();o+=1)e[o]=n.getAt(o);for(o=0;o<t.getLength();o+=1)e[o]^=C.gexp(C.glog(t.getAt(o))+r);return k(e,0).mod(t)}};return n}var A=function(){var t=[[1,26,19],[1,26,16],[1,26,13],[1,26,9],[1,44,34],[1,44,28],[1,44,22],[1,44,16],[1,70,55],[1,70,44],[2,35,17],[2,35,13],[1,100,80],[2,50,32],[2,50,24],[4,25,9],[1,134,108],[2,67,43],[2,33,15,2,34,16],[2,33,11,2,34,12],[2,86,68],[4,43,27],[4,43,19],[4,43,15],[2,98,78],[4,49,31],[2,32,14,4,33,15],[4,39,13,1,40,14],[2,121,97],[2,60,38,2,61,39],[4,40,18,2,41,19],[4,40,14,2,41,15],[2,146,116],[3,58,36,2,59,37],[4,36,16,4,37,17],[4,36,12,4,37,13],[2,86,68,2,87,69],[4,69,43,1,70,44],[6,43,19,2,44,20],[6,43,15,2,44,16],[4,101,81],[1,80,50,4,81,51],[4,50,22,4,51,23],[3,36,12,8,37,13],[2,116,92,2,117,93],[6,58,36,2,59,37],[4,46,20,6,47,21],[7,42,14,4,43,15],[4,133,107],[8,59,37,1,60,38],[8,44,20,4,45,21],[12,33,11,4,34,12],[3,145,115,1,146,116],[4,64,40,5,65,41],[11,36,16,5,37,17],[11,36,12,5,37,13],[5,109,87,1,110,88],[5,65,41,5,66,42],[5,54,24,7,55,25],[11,36,12,7,37,13],[5,122,98,1,123,99],[7,73,45,3,74,46],[15,43,19,2,44,20],[3,45,15,13,46,16],[1,135,107,5,136,108],[10,74,46,1,75,47],[1,50,22,15,51,23],[2,42,14,17,43,15],[5,150,120,1,151,121],[9,69,43,4,70,44],[17,50,22,1,51,23],[2,42,14,19,43,15],[3,141,113,4,142,114],[3,70,44,11,71,45],[17,47,21,4,48,22],[9,39,13,16,40,14],[3,135,107,5,136,108],[3,67,41,13,68,42],[15,54,24,5,55,25],[15,43,15,10,44,16],[4,144,116,4,145,117],[17,68,42],[17,50,22,6,51,23],[19,46,16,6,47,17],[2,139,111,7,140,112],[17,74,46],[7,54,24,16,55,25],[34,37,13],[4,151,121,5,152,122],[4,75,47,14,76,48],[11,54,24,14,55,25],[16,45,15,14,46,16],[6,147,117,4,148,118],[6,73,45,14,74,46],[11,54,24,16,55,25],[30,46,16,2,47,17],[8,132,106,4,133,107],[8,75,47,13,76,48],[7,54,24,22,55,25],[22,45,15,13,46,16],[10,142,114,2,143,115],[19,74,46,4,75,47],[28,50,22,6,51,23],[33,46,16,4,47,17],[8,152,122,4,153,123],[22,73,45,3,74,46],[8,53,23,26,54,24],[12,45,15,28,46,16],[3,147,117,10,148,118],[3,73,45,23,74,46],[4,54,24,31,55,25],[11,45,15,31,46,16],[7,146,116,7,147,117],[21,73,45,7,74,46],[1,53,23,37,54,24],[19,45,15,26,46,16],[5,145,115,10,146,116],[19,75,47,10,76,48],[15,54,24,25,55,25],[23,45,15,25,46,16],[13,145,115,3,146,116],[2,74,46,29,75,47],[42,54,24,1,55,25],[23,45,15,28,46,16],[17,145,115],[10,74,46,23,75,47],[10,54,24,35,55,25],[19,45,15,35,46,16],[17,145,115,1,146,116],[14,74,46,21,75,47],[29,54,24,19,55,25],[11,45,15,46,46,16],[13,145,115,6,146,116],[14,74,46,23,75,47],[44,54,24,7,55,25],[59,46,16,1,47,17],[12,151,121,7,152,122],[12,75,47,26,76,48],[39,54,24,14,55,25],[22,45,15,41,46,16],[6,151,121,14,152,122],[6,75,47,34,76,48],[46,54,24,10,55,25],[2,45,15,64,46,16],[17,152,122,4,153,123],[29,74,46,14,75,47],[49,54,24,10,55,25],[24,45,15,46,46,16],[4,152,122,18,153,123],[13,74,46,32,75,47],[48,54,24,14,55,25],[42,45,15,32,46,16],[20,147,117,4,148,118],[40,75,47,7,76,48],[43,54,24,22,55,25],[10,45,15,67,46,16],[19,148,118,6,149,119],[18,75,47,31,76,48],[34,54,24,34,55,25],[20,45,15,61,46,16]],r=function(t,r){var e={};return e.totalCount=t,e.dataCount=r,e},e={};return e.getRSBlocks=function(e,n){var o=function(r,e){switch(e){case g.L:return t[4*(r-1)+0];case g.M:return t[4*(r-1)+1];case g.Q:return t[4*(r-1)+2];case g.H:return t[4*(r-1)+3];default:return}}(e,n);if(void 0===o)throw"bad rs block @ typeNumber:"+e+"/errorCorrectionLevel:"+n;for(var i=o.length/3,a=[],u=0;u<i;u+=1)for(var f=o[3*u+0],c=o[3*u+1],l=o[3*u+2],h=0;h<f;h+=1)a.push(r(c,l));return a},e}(),b=function(){var t=[],r=0,e={getBuffer:function(){return t},getAt:function(r){var e=Math.floor(r/8);return 1==(t[e]>>>7-r%8&1)},put:function(t,r){for(var n=0;n<r;n+=1)e.putBit(1==(t>>>r-n-1&1))},getLengthInBits:function(){return r},putBit:function(e){var n=Math.floor(r/8);t.length<=n&&t.push(0),e&&(t[n]|=128>>>r%8),r+=1}};return e},M=function(t){var r=a,e=t,n={getMode:function(){return r},getLength:function(t){return e.length},write:function(t){for(var r=e,n=0;n+2<r.length;)t.put(o(r.substring(n,n+3)),10),n+=3;n<r.length&&(r.length-n==1?t.put(o(r.substring(n,n+1)),4):r.length-n==2&&t.put(o(r.substring(n,n+2)),7))}},o=function(t){for(var r=0,e=0;e<t.length;e+=1)r=10*r+i(t.charAt(e));return r},i=function(t){if("0"<=t&&t<="9")return t.charCodeAt(0)-"0".charCodeAt(0);throw"illegal char :"+t};return n},x=function(t){var r=u,e=t,n={getMode:function(){return r},getLength:function(t){return e.length},write:function(t){for(var r=e,n=0;n+1<r.length;)t.put(45*o(r.charAt(n))+o(r.charAt(n+1)),11),n+=2;n<r.length&&t.put(o(r.charAt(n)),6)}},o=function(t){if("0"<=t&&t<="9")return t.charCodeAt(0)-"0".charCodeAt(0);if("A"<=t&&t<="Z")return t.charCodeAt(0)-"A".charCodeAt(0)+10;switch(t){case" ":return 36;case"$":return 37;case"%":return 38;case"*":return 39;case"+":return 40;case"-":return 41;case".":return 42;case"/":return 43;case":":return 44;default:throw"illegal char :"+t}};return n},m=function(r){var e=f,n=t.stringToBytes(r),o={getMode:function(){return e},getLength:function(t){return n.length},write:function(t){for(var r=0;r<n.length;r+=1)t.put(n[r],8)}};return o},L=function(r){var e=c,n=t.stringToBytesFuncs.SJIS;if(!n)throw"sjis not supported.";!function(){var t=n("友");if(2!=t.length||38726!=(t[0]<<8|t[1]))throw"sjis not supported."}();var o=n(r),i={getMode:function(){return e},getLength:function(t){return~~(o.length/2)},write:function(t){for(var r=o,e=0;e+1<r.length;){var n=(255&r[e])<<8|255&r[e+1];if(33088<=n&&n<=40956)n-=33088;else{if(!(57408<=n&&n<=60351))throw"illegal char at "+(e+1)+"/"+n;n-=49472}n=192*(n>>>8&255)+(255&n),t.put(n,13),e+=2}if(e<r.length)throw"illegal char at "+(e+1)}};return i},D=function(){var t=[],r={writeByte:function(r){t.push(255&r)},writeShort:function(t){r.writeByte(t),r.writeByte(t>>>8)},writeBytes:function(t,e,n){e=e||0,n=n||t.length;for(var o=0;o<n;o+=1)r.writeByte(t[o+e])},writeString:function(t){for(var e=0;e<t.length;e+=1)r.writeByte(t.charCodeAt(e))},toByteArray:function(){return t},toString:function(){var r="";r+="[";for(var e=0;e<t.length;e+=1)e>0&&(r+=","),r+=t[e];return r+="]"}};return r},S=function(t){var r=t,e=0,n=0,o=0,i={read:function(){for(;o<8;){if(e>=r.length){if(0==o)return-1;throw"unexpected end of file./"+o}var t=r.charAt(e);if(e+=1,"="==t)return o=0,-1;t.match(/^\s$/)||(n=n<<6|a(t.charCodeAt(0)),o+=6)}var i=n>>>o-8&255;return o-=8,i}},a=function(t){if(65<=t&&t<=90)return t-65;if(97<=t&&t<=122)return t-97+26;if(48<=t&&t<=57)return t-48+52;if(43==t)return 62;if(47==t)return 63;throw"c:"+t};return i},I=function(t,r,e){for(var n=function(t,r){var e=t,n=r,o=new Array(t*r),i={setPixel:function(t,r,n){o[r*e+t]=n},write:function(t){t.writeString("GIF87a"),t.writeShort(e),t.writeShort(n),t.writeByte(128),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(0),t.writeByte(255),t.writeByte(255),t.writeByte(255),t.writeString(","),t.writeShort(0),t.writeShort(0),t.writeShort(e),t.writeShort(n),t.writeByte(0);var r=a(2);t.writeByte(2);for(var o=0;r.length-o>255;)t.writeByte(255),t.writeBytes(r,o,255),o+=255;t.writeByte(r.length-o),t.writeBytes(r,o,r.length-o),t.writeByte(0),t.writeString(";")}},a=function(t){for(var r=1<<t,e=1+(1<<t),n=t+1,i=u(),a=0;a<r;a+=1)i.add(String.fromCharCode(a));i.add(String.fromCharCode(r)),i.add(String.fromCharCode(e));var f,c,g,l=D(),h=(f=l,c=0,g=0,{write:function(t,r){if(t>>>r!=0)throw"length over";for(;c+r>=8;)f.writeByte(255&(t<<c|g)),r-=8-c,t>>>=8-c,g=0,c=0;g|=t<<c,c+=r},flush:function(){c>0&&f.writeByte(g)}});h.write(r,n);var s=0,v=String.fromCharCode(o[s]);for(s+=1;s<o.length;){var d=String.fromCharCode(o[s]);s+=1,i.contains(v+d)?v+=d:(h.write(i.indexOf(v),n),i.size()<4095&&(i.size()==1<<n&&(n+=1),i.add(v+d)),v=d)}return h.write(i.indexOf(v),n),h.write(e,n),h.flush(),l.toByteArray()},u=function(){var t={},r=0,e={add:function(n){if(e.contains(n))throw"dup key:"+n;t[n]=r,r+=1},size:function(){return r},indexOf:function(r){return t[r]},contains:function(r){return void 0!==t[r]}};return e};return i}(t,r),o=0;o<r;o+=1)for(var i=0;i<t;i+=1)n.setPixel(i,o,e(i,o));var a=D();n.write(a);for(var u=function(){var t=0,r=0,e=0,n="",o={},i=function(t){n+=String.fromCharCode(a(63&t))},a=function(t){if(t<0);else{if(t<26)return 65+t;if(t<52)return t-26+97;if(t<62)return t-52+48;if(62==t)return 43;if(63==t)return 47}throw"n:"+t};return o.writeByte=function(n){for(t=t<<8|255&n,r+=8,e+=1;r>=6;)i(t>>>r-6),r-=6},o.flush=function(){if(r>0&&(i(t<<6-r),t=0,r=0),e%3!=0)for(var o=3-e%3,a=0;a<o;a+=1)n+="="},o.toString=function(){return n},o}(),f=a.toByteArray(),c=0;c<f.length;c+=1)u.writeByte(f[c]);return u.flush(),"data:image/gif;base64,"+u};return t}();qrcode.stringToBytesFuncs["UTF-8"]=function(t){return function(t){for(var r=[],e=0;e<t.length;e++){var n=t.charCodeAt(e);n<128?r.push(n):n<2048?r.push(192|n>>6,128|63&n):n<55296||n>=57344?r.push(224|n>>12,128|n>>6&63,128|63&n):(e++,n=65536+((1023&n)<<10|1023&t.charCodeAt(e)),r.push(240|n>>18,128|n>>12&63,128|n>>6&63,128|63&n))}return r}(t)},function(t){"function"==typeof define&&define.amd?define([],t):"object"==typeof exports&&(module.exports=t())}(function(){return qrcode});
  /* ---- widget ---- */
  var VERSION = '1.0.0';
  var TEXTS = {
    alipayTitle: '支付宝付款',
    scanAlipay: '请使用支付宝「扫一扫」扫码付款',
    openAlipay: '打开支付宝付款',
    showQr: '显示二维码',
    saveQr: '长按二维码保存，在支付宝「扫一扫」中选择相册识别',
    wechatTip: '当前在微信 / QQ 内置浏览器中，无法唤起支付宝，请点击右上角选择「在浏览器中打开」',
    amount: '应付金额',
    cryptoTitle: '转账付款',
    address: '收款地址',
    payAmount: '转账数量',
    copy: '复制',
    copied: '已复制',
    copyFail: '复制失败，请手动选择复制',
    exactAmount: '请务必转入恰好上述数量，少付或多付都无法自动到账；网络手续费由您承担。',
    confirmTip: '转账完成后请勿关闭本页，链上确认通常需要 1–3 分钟，到账后自动完成。',
    remain: '剩余支付时间',
    paid: '支付成功',
    paidTip: '正在返回…',
    expired: '订单已过期',
    expiredTip: '请返回重新下单',
    error: '支付信息加载失败',
    retry: '重试',
    close: '关闭',
    back: '返回',
    poweredBy: 'Paytaro 提供技术支持'
  };
  var CSS = '.pqr{font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"PingFang SC","Hiragino Sans GB","Microsoft YaHei",sans-serif;color:#1f2937;box-sizing:border-box}.pqr *{box-sizing:border-box}'
    + '.pqr-card{background:#fff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.12);padding:22px 22px 16px;width:100%;max-width:400px;margin:0 auto;position:relative}'
    + '.pqr-head{display:flex;align-items:center;gap:10px;margin-bottom:14px}.pqr-head img{width:28px;height:28px;border-radius:6px}.pqr-title{font-size:17px;font-weight:600;flex:1}'
    + '.pqr-close{border:0;background:transparent;font-size:22px;line-height:1;color:#9ca3af;cursor:pointer;padding:2px 6px}.pqr-close:hover{color:#374151}'
    + '.pqr-amount{text-align:center;margin:6px 0 14px}.pqr-amount small{display:block;color:#6b7280;font-size:12px}.pqr-amount b{font-size:28px;font-weight:700;color:#111827}'
    + '.pqr-qr{display:flex;justify-content:center;align-items:center;position:relative;margin:0 auto 10px;width:220px;height:220px;border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#fff}.pqr-qr svg{width:100%;height:100%;display:block}'
    + '.pqr-qr.pqr-qr-dense{width:320px;height:320px}'
    + '.pqr-qr img.pqr-logo{position:absolute;left:50%;top:50%;width:44px;height:44px;margin:-22px 0 0 -22px;border-radius:50%;background:#fff;padding:3px}'
    + '.pqr-tip{text-align:center;color:#6b7280;font-size:13px;margin:6px 0}.pqr-warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:8px;padding:8px 10px;font-size:12px;margin:10px 0}'
    + '.pqr-btn{display:block;width:100%;border:0;border-radius:10px;padding:12px;font-size:16px;font-weight:600;cursor:pointer;text-align:center;text-decoration:none;color:#fff;background:#1677ff;margin:8px 0}.pqr-btn:hover{background:#0958d9}.pqr-btn.pqr-sec{background:#f3f4f6;color:#374151;font-weight:500;font-size:14px;padding:10px}.pqr-btn.pqr-sec:hover{background:#e5e7eb}'
    + '.pqr-field{margin:10px 0}.pqr-field label{display:block;font-size:12px;color:#6b7280;margin-bottom:4px}.pqr-row{display:flex;gap:6px;align-items:stretch}.pqr-row code{flex:1;display:block;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:9px 10px;font-size:13px;word-break:break-all;line-height:1.4;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}.pqr-row code.pqr-big{font-size:18px;font-weight:700;color:#111827}'
    + '.pqr-copy{border:1px solid #d1d5db;background:#fff;border-radius:8px;padding:0 12px;cursor:pointer;font-size:13px;white-space:nowrap;color:#374151}.pqr-copy:hover{background:#f3f4f6}.pqr-copy.ok{border-color:#16a34a;color:#16a34a}'
    + '.pqr-count{text-align:center;color:#6b7280;font-size:13px;margin-top:10px}.pqr-count b{color:#dc2626;font-variant-numeric:tabular-nums}'
    + '.pqr-state{text-align:center;padding:26px 0 14px}.pqr-state .pqr-ico{width:64px;height:64px;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:34px;color:#fff}.pqr-ok .pqr-ico{background:#16a34a}.pqr-bad .pqr-ico{background:#9ca3af}.pqr-err .pqr-ico{background:#dc2626}.pqr-state h3{margin:0 0 4px;font-size:18px}.pqr-state p{margin:0;color:#6b7280}'
    + '.pqr-foot{text-align:center;color:#9ca3af;font-size:11px;margin-top:12px}'
    + '.pqr-overlay{position:fixed;inset:0;background:rgba(17,24,39,.55);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;overflow:auto}'
    + '.pqr-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 12px;background:#f3f4f6}'
    + '@media (max-width:480px){.pqr-card{padding:18px 16px 12px}.pqr-qr{width:200px;height:200px}.pqr-qr.pqr-qr-dense{width:280px;height:280px}}';

  function injectCss() {
    if (document.getElementById('pqr-style')) return;
    var st = document.createElement('style'); st.id = 'pqr-style'; st.textContent = CSS;
    (document.head || document.documentElement).appendChild(st);
  }
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function ua() { return (global.navigator && navigator.userAgent) || ''; }
  function isMobile() { return /Android|iPhone|iPad|iPod|Mobile|HarmonyOS/i.test(ua()); }
  function isWeChat() { return /MicroMessenger|QQ\//i.test(ua()); }
  function fmtAmount(n, currency) {
    var v = Number(n); if (isNaN(v)) return String(n);
    var s = (String(currency || '').toUpperCase() === 'CNY') ? v.toFixed(2) : v.toFixed(6).replace(/\.?0+$/, '');
    return s;
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function copyText(text, btn, T) {
    function done(ok) {
      if (!btn) return; var old = btn.textContent;
      btn.textContent = ok ? T.copied : T.copyFail; btn.className = 'pqr-copy' + (ok ? ' ok' : '');
      setTimeout(function () { btn.textContent = old; btn.className = 'pqr-copy'; }, 1500);
    }
    if (navigator.clipboard && global.isSecureContext) {
      navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(legacyCopy(text)); });
    } else { done(legacyCopy(text)); }
  }
  function legacyCopy(text) {
    try { var ta = document.createElement('textarea'); ta.value = text; ta.setAttribute('readonly', ''); ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
      document.body.appendChild(ta); ta.select(); ta.setSelectionRange(0, text.length); var ok = document.execCommand('copy'); document.body.removeChild(ta); return ok; } catch (e) { return false; }
  }
  // 支付宝手机网站支付返回的是 ~800 字符的网关签名直链，二维码会到 version 20+（100 多个模块）。
  // 内容长时改用 L 级纠错（少 10% 模块）、放大展示框并去掉中间 logo，保证每个模块 ≥ 2.8px 可被手机扫到。
  var QR_DENSE_MODULES = 80;
  function qrSvg(text) {
    var q = qrcode(0, text.length > 300 ? 'L' : 'M'); q.addData(text); q.make();
    return { svg: q.createSvgTag({ cellSize: 4, margin: 0, scalable: true }), modules: q.getModuleCount() };
  }
  function qrBoxFor(p) {
    var box = el('div', 'pqr-qr'), r = qrSvg(p.data); box.innerHTML = r.svg;
    if (r.modules > QR_DENSE_MODULES) box.className += ' pqr-qr-dense';
    else if (p.icon) { var lg = el('img', 'pqr-logo'); lg.src = p.icon; lg.alt = ''; box.appendChild(lg); }
    return box;
  }
  function httpReq(method, url, body, cb) {
    try {
      var x = new XMLHttpRequest(); x.open(method || 'GET', url, true); x.setRequestHeader('Accept', 'application/json');
      if (body != null) x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      x.withCredentials = true;
      x.onreadystatechange = function () { if (x.readyState !== 4) return; var d = null; try { d = JSON.parse(x.responseText); } catch (e) { }
        cb(x.status >= 200 && x.status < 300 && d ? null : new Error('HTTP ' + x.status), d); };
      x.send(body != null ? body : null);
    } catch (e) { cb(e); }
  }

  function Widget(container, opts) {
    this.c = container; this.o = opts || {}; this.T = {}; var k;
    for (k in TEXTS) this.T[k] = TEXTS[k]; for (k in (this.o.texts || {})) this.T[k] = this.o.texts[k];
    this.order = this.o.order || {}; this.payment = this.o.payment || null;
    this.t0 = Date.now(); this.timers = []; this.dead = false; this.pollFails = 0;
    this.card = null;
  }
  Widget.prototype.destroy = function () {
    this.dead = true; for (var i = 0; i < this.timers.length; i++) clearTimeout(this.timers[i]); this.timers = [];
    if (this.wrap && this.wrap.parentNode) this.wrap.parentNode.removeChild(this.wrap);
    if (this.c) this.c.__pqr = null;
  };
  Widget.prototype.later = function (fn, ms) {
    var self = this, t = setTimeout(function () { var i = self.timers.indexOf(t); if (i >= 0) self.timers.splice(i, 1); if (!self.dead) fn(); }, ms);
    this.timers.push(t); return t;
  };
  Widget.prototype.mount = function () {
    injectCss(); var mode = this.o.mode || 'inline', self = this;
    this.card = el('div', 'pqr pqr-card');
    if (mode === 'modal') {
      this.wrap = el('div', 'pqr-overlay'); this.wrap.appendChild(this.card); (this.c || document.body).appendChild(this.wrap);
      this.wrap.addEventListener('click', function (e) { if (e.target === self.wrap) self.close(); });
    } else if (mode === 'page') {
      this.wrap = el('div', 'pqr-page'); this.wrap.appendChild(this.card); this.c.appendChild(this.wrap);
    } else { this.wrap = this.card; this.c.innerHTML = ''; this.c.appendChild(this.card); }
    if (this.c) this.c.__pqr = this;
    this.renderMain();
  };
  Widget.prototype.close = function () { if (this.o.onClose) this.o.onClose(this); this.destroy(); };
  Widget.prototype.head = function (title) {
    var h = el('div', 'pqr-head'), p = this.payment || {};
    if (p.icon) { var im = el('img'); im.src = p.icon; im.alt = ''; h.appendChild(im); }
    h.appendChild(el('div', 'pqr-title', esc(title)));
    if (this.o.mode === 'modal' || this.o.onClose) { var b = el('button', 'pqr-close', '&times;'); b.type = 'button'; b.title = this.T.close; var self = this; b.onclick = function () { self.close(); }; h.appendChild(b); }
    return h;
  };
  Widget.prototype.foot = function () { return this.o.hideBrand ? el('div') : el('div', 'pqr-foot', esc(this.T.poweredBy)); };
  Widget.prototype.renderMain = function () {
    var st = String(this.order.status || 'UNPAID').toUpperCase();
    if (st === 'PAID' || st === 'SUCCESS') return this.renderPaid();
    if (st === 'CANCEL' || st === 'EXPIRED') return this.renderExpired();
    if (!this.payment || !this.payment.data) return this.renderError(this.T.error);
    var lt = String(this.payment.link_type || '').toLowerCase();
    var isAlipay = lt === 'h5' || lt === 'pc' || String(this.payment.type).toLowerCase() === 'alipay';
    if (isAlipay) this.renderAlipay(); else this.renderCrypto();
    this.startCountdown(); this.startPolling();
  };
  Widget.prototype.renderAlipay = function () {
    var T = this.T, p = this.payment, o = this.order, card = this.card, self = this; card.innerHTML = '';
    card.appendChild(this.head(p.name || T.alipayTitle));
    var amt = el('div', 'pqr-amount', '<small>' + esc(T.amount) + '</small><b>¥ ' + esc(fmtAmount(p.pay_amount != null ? p.pay_amount : o.order_amount, 'CNY')) + '</b>');
    card.appendChild(amt);
    var mobile = isMobile();
    if (mobile && isWeChat()) card.appendChild(el('div', 'pqr-warn', esc(T.wechatTip)));
    var qrBox = qrBoxFor(p);
    if (mobile) {
      var a = el('a', 'pqr-btn', esc(T.openAlipay)); a.href = p.mobile_url || p.data; a.rel = 'noopener'; card.appendChild(a);
      var toggle = el('button', 'pqr-btn pqr-sec', esc(T.showQr)); toggle.type = 'button'; card.appendChild(toggle);
      qrBox.style.display = 'none'; var tip = el('div', 'pqr-tip', esc(T.saveQr)); tip.style.display = 'none';
      card.appendChild(qrBox); card.appendChild(tip);
      toggle.onclick = function () { qrBox.style.display = ''; tip.style.display = ''; toggle.style.display = 'none'; };
    } else {
      card.appendChild(qrBox); card.appendChild(el('div', 'pqr-tip', esc(T.scanAlipay)));
      if (lt(p) === 'pc') { var b = el('a', 'pqr-btn pqr-sec', '在支付宝网页收银台付款'); b.href = p.data; b.target = '_blank'; b.rel = 'noopener'; card.appendChild(b); }
    }
    this.countEl = el('div', 'pqr-count'); card.appendChild(this.countEl); card.appendChild(this.foot());
    function lt(x) { return String(x.link_type || '').toLowerCase(); }
  };
  Widget.prototype.renderCrypto = function () {
    var T = this.T, p = this.payment, card = this.card, self = this; card.innerHTML = '';
    card.appendChild(this.head(p.name || T.cryptoTitle));
    var cur = p.pay_currency || '', amount = fmtAmount(p.pay_amount, cur);
    var qrBox = qrBoxFor(p);
    card.appendChild(qrBox);
    card.appendChild(field(T.payAmount, amount + (cur ? ' ' + cur : ''), amount, true));
    card.appendChild(field(T.address, p.data, p.data, false));
    card.appendChild(el('div', 'pqr-warn', esc(T.exactAmount)));
    card.appendChild(el('div', 'pqr-tip', esc(T.confirmTip)));
    this.countEl = el('div', 'pqr-count'); card.appendChild(this.countEl); card.appendChild(this.foot());
    function field(label, shown, copyVal, big) {
      var f = el('div', 'pqr-field'); f.appendChild(el('label', null, esc(label)));
      var row = el('div', 'pqr-row'); row.appendChild(el('code', big ? 'pqr-big' : null, esc(shown)));
      var b = el('button', 'pqr-copy', esc(T.copy)); b.type = 'button'; b.onclick = function () { copyText(copyVal, b, T); }; row.appendChild(b); f.appendChild(row); return f;
    }
  };
  Widget.prototype.renderState = function (cls, icon, title, tip, actions) {
    var card = this.card; card.innerHTML = '';
    if (this.o.mode === 'modal') card.appendChild(this.head(''));
    var s = el('div', 'pqr-state ' + cls); s.appendChild(el('div', 'pqr-ico', icon)); s.appendChild(el('h3', null, esc(title))); if (tip) s.appendChild(el('p', null, esc(tip)));
    card.appendChild(s); for (var i = 0; i < (actions || []).length; i++) card.appendChild(actions[i]); card.appendChild(this.foot());
  };
  Widget.prototype.renderPaid = function () {
    var self = this, T = this.T; this.stop();
    this.renderState('pqr-ok', '&#10003;', T.paid, this.order.return_url ? T.paidTip : '');
    if (this.o.onPaid) return this.o.onPaid(this.order, this);
    this.later(function () { if (self.order.return_url) global.location.href = self.order.return_url; else global.location.reload(); }, 2500);
  };
  Widget.prototype.renderExpired = function () {
    var self = this, T = this.T; this.stop();
    var b = el('button', 'pqr-btn pqr-sec', esc(T.back)); b.type = 'button'; b.onclick = function () { self.goBack(); };
    this.renderState('pqr-bad', '&#9203;', T.expired, T.expiredTip, [b]);
    if (this.o.onExpired) this.o.onExpired(this.order, this);
  };
  Widget.prototype.renderError = function (msg) {
    var self = this, T = this.T; this.stop();
    var r = el('button', 'pqr-btn', esc(T.retry)); r.type = 'button'; r.onclick = function () { global.location.reload(); };
    var b = el('button', 'pqr-btn pqr-sec', esc(T.back)); b.type = 'button'; b.onclick = function () { self.goBack(); };
    this.renderState('pqr-err', '!', T.error, msg && msg !== T.error ? msg : '', [r, b]);
  };
  Widget.prototype.goBack = function () {
    if (this.o.onClose && this.o.mode === 'modal') return this.close();
    if (this.order.return_url) global.location.href = this.order.return_url; else if (global.history.length > 1) global.history.back(); else global.location.href = '/';
  };
  Widget.prototype.stop = function () { for (var i = 0; i < this.timers.length; i++) clearTimeout(this.timers[i]); this.timers = []; };
  Widget.prototype.remaining = function () {
    var o = this.order; if (!o.expired_at) return null;
    var base = o.server_time ? Number(o.server_time) : Math.floor(this.t0 / 1000);
    return Math.floor(Number(o.expired_at) - base - (Date.now() - this.t0) / 1000);
  };
  Widget.prototype.startCountdown = function () {
    var self = this, T = this.T;
    (function tick() {
      if (self.dead || !self.countEl) return;
      var r = self.remaining();
      if (r === null) { self.countEl.innerHTML = ''; return; }
      if (r <= 0) return self.renderExpired();
      self.countEl.innerHTML = esc(T.remain) + ' <b>' + pad(Math.floor(r / 60)) + ':' + pad(r % 60) + '</b>';
      self.later(tick, 1000);
    })();
  };
  Widget.prototype.startPolling = function () {
    var self = this, s = this.o.status; if (!s || !s.url) return;
    var interval = s.interval || 3000;
    (function poll() {
      if (self.dead) return;
      if (document.hidden) return self.later(poll, 1500);
      httpReq(s.method || 'GET', s.url, s.body != null ? s.body : null, function (err, data) {
        if (self.dead) return;
        if (err || !data) { self.pollFails++; return self.later(poll, Math.min(10000, interval * (1 + self.pollFails))); }
        self.pollFails = 0;
        var st = String((s.parse ? s.parse(data) : data.status) || '').toUpperCase();
        if (data.return_url && !self.order.return_url) self.order.return_url = data.return_url;
        if (st === 'PAID' || st === 'SUCCESS') { self.order.status = st; return self.renderPaid(); }
        if (st === 'CANCEL' || st === 'EXPIRED') { self.order.status = st; return self.renderExpired(); }
        self.later(poll, interval);
      });
    })();
  };

  var PaytaroQR = {
    version: VERSION,
    qr: qrSvg,
    isMobile: isMobile,
    isWeChat: isWeChat,
    render: function (container, opts) {
      var c = typeof container === 'string' ? document.querySelector(container) : container;
      if (!c && (opts || {}).mode !== 'modal') throw new Error('PaytaroQR: container not found');
      if (c && c.__pqr) c.__pqr.destroy();
      var w = new Widget(c, opts); w.mount(); return w;
    },
    destroy: function (container) { var c = typeof container === 'string' ? document.querySelector(container) : container; if (c && c.__pqr) c.__pqr.destroy(); }
  };
  global.PaytaroQR = PaytaroQR;
})(window);
