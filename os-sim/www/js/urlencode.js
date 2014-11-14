function urlencode(textoAcodificar)
{
	var nocodificar = "0123456789"+"ABCDEFGHIJKLMNOPQRSTUVWXYZ"+"abcdefghijklmnopqrstuvwxyz" +"-_.!~*'()";
	var HEX = "0123456789ABCDEF";
	var codificado = "";
	if (typeof(textoAcodificar) != 'undefined')
		for (var i = 0; i < textoAcodificar.length; i++ ) {
			var ch = textoAcodificar.charAt(i);
		    if (ch == " ") {
			    codificado += "+";
			} else if (nocodificar.indexOf(ch) != -1) {
			    codificado += ch;
			} else {
			    var charCode = ch.charCodeAt(0);
				if (charCode > 255) {
				   /* alert( "Caracter Unicode '"+ch+"' no puede ser codificado utilizando la codificación URL estandar.\n" +
					          "(sólo soporta caracteres de 8-bit.)\n" +
							  "Será sustituido por un símbolo de suma (+)." ); */
					codificado += "+";
				} else {
					codificado += "%";
					codificado += HEX.charAt((charCode >> 4) & 0xF);
					codificado += HEX.charAt(charCode & 0xF);
				}
			}
		}
	return codificado;
};

function urldecode(codificado){
   var HEXCHARS = "0123456789ABCDEFabcdef"; 
   var textoAcodificar = "";
   var i = 0;
   if (typeof(codificado) != 'undefined')
		while (i < codificado.length) {
			var ch = codificado.charAt(i);
			if (ch == "+") {
				textoAcodificar += " ";
				i++;
			} else if (ch == "%") {
				if (i < (codificado.length-2) 
						&& HEXCHARS.indexOf(codificado.charAt(i+1)) != -1 
						&& HEXCHARS.indexOf(codificado.charAt(i+2)) != -1 ) {
					textoAcodificar += unescape( codificado.substr(i,3) );
					i += 3;
				} else {
					//alert( 'Bad escape combination near ...' + codificado.substr(i) );
					textoAcodificar += "%[ERROR]";
					i++;
				}
			} else {
				textoAcodificar += ch;
				i++;
			}
		}
   return textoAcodificar;
};
