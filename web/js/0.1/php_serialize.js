/**
 * Object PHP_Serializer
 * 	JavaScript to PHP serialize / unserialize class.
 * This class converts php variables to javascript and vice versa.
 *
 * PARSABLE JAVASCRIPT < === > PHP VARIABLES:
 *	[ JAVASCRIPT TYPE ]		[ PHP TYPE ]
 *	Array		< === > 	array
 *	Object		< === > 	class (*)
 *	String		< === > 	string
 *	Boolean		< === > 	boolean
 *	null		< === > 	null
 *	Number		< === > 	int or double
 *	Date		< === > 	class
 *	Error		< === > 	class
 *	Function	< === > 	class (*)
 *
 * (*) NOTE:
 * Any PHP serialized class requires the native PHP class to be used, then it's not a
 * PHP => JavaScript converter, it's just a usefull serilizer class for each
 * compatible JS and PHP variable types.
 * Lambda, Resources or other dedicated PHP variables are not usefull for JavaScript.
 * There are same restrictions for javascript functions*** too then these will not be sent.
 *
 * *** function test(); alert(php.serialize(test)); will be empty string but
 * *** mytest = new test(); will be sent as test class to php
 * _____________________________________________
 *
 * EXAMPLE:
 *	var php = new PHP_Serializer(); // use new PHP_Serializer(true); to enable UTF8 compatibility
 *	alert(php.unserialize(php.serialize(somevar)));
 *	// should alert the original value of somevar
 * ---------------------------------------------
 * @author              Andrea Giammarchi
 * @site		www.devpro.it
 * @date                2005/11/26
 * @lastmod             2006/05/15 19:00 [modified stringBytes method and removed replace for UTF8 and \r\n]
 * 			[add UTF8 var again, PHP strings if are not encoded with utf8_encode aren't compatible with this object]
 *			[Partially rewrote for a better stability and compatibility with Safari or KDE based browsers]
 *			[UTF-8 now has a native support, strings are converted automatically with ISO or UTF-8 charset]
 *
 * @specialthanks	Fabio Sutto, Kentaromiura, Kroc Camen, Cecile Maigrot, John C.Scott, Matteo Galli
 *
 * @version             2.2, tested on FF 1.0, 1.5, IE 5, 5.5, 6, 7 beta 2, Opera 8.5, Konqueror 3.5, Safari 2.0.3
 */
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('7 5(d){7 19(v){m s;1r(v){I 18:s="N;";C;1y:s=8[8.j(v)]?8[8.j(v)](v):8[8.j(M)](v);C};g s};7 1a(s){e=0;h=s;g 8[h.p(e,1)]()};7 z(s){g s.F};7 1m(s){m c,b=0,l=s.F;17(l){c=s.1c(--l);b+=(c<1f)?1:((c<1o)?2:((c<1d)?3:4))};g b};7 j(v){g v.U.P()};7 y(v){m f;1r(1z(v)){I("1K"||v H 1J):f="A";C;I("1H"||v H 1s):f="B";C;I("1B"||v H 1G):f="T";C;I("7"||v H 15):f="S";C;1y:f=(v H 1F)?"Q":"14";C};g f};7 J(c){g(c==="[7]"||c==="(1D 15)")};7 u(v){m b,n,a=0,s=[];1M(b 1A v){n=v[b]==18;D(n||v[b].U!=15){s[a]=[(!1E(b)&&R(b).P()===b?8.B(b):8.A(b)),(n?"N;":8[8.j(v[b])]?8[8.j(v[b])](v[b]):8[8.j(M)](v[b]))].q("");++a}};g[a,s.q("")]};7 T(v){g["b:",(v?"1":"0"),";"].q("")};7 B(v){m s=v.P();g(s.r(".")<0?["i:",s,";"]:["d:",s,";"]).q("")};7 A(v){g["s:",v.F,":\\"",v,"\\";"].q("")};7 13(v){g["s:",8.z(v),":\\"",v,"\\";"].q("")};7 Q(v){m s=8.u(v);g["a:",s[0],":{",s[1],"}"].q("")};7 14(v){m o=8.j(v),n=o.p(w,(o.r("(")-w)),s=8.u(v);g["O:",8.z(n),":\\"",n,"\\":",s[0],":{",s[1],"}"].q("")};7 1t(v){m o=8.j(v),n=o.p(w,(o.r("(")-w)),s=8.u(v);D(n.Z(0)===" ")n=n.1L(1);g["O:",8.z(n),":\\"",n,"\\":",s[0],":{",s[1],"}"].q("")};7 1w(v){m o=v.U.P(),n=8.J(o)?"1x":o.p(w,(o.r("(")-w)),s=8.u(v);g["O:",8.z(n),":\\"",n,"\\":",s[0],":{",s[1],"}"].q("")};7 S(v){g""};7 G(b){m a,k;++e;a=h.r(":",++e);k=R(h.p(e,(a-e)))+1;e=a+2;17(--k)b[8[h.p(e,1)]()]=8[h.p(e,1)]();g b};7 1q(){m b=h.p((e+2),1)==="1"?1p:1j;e+=4;g b};7 1n(){m a=h.r(";",(e+1))-2,n=1s(h.p((e+2),(a-e)));e=a+3;g n};7 1b(){m c,x,t,L,E=0;e+=2;x=h.p(e,(h.r(":",e)-e));t=R(x);L=x=e+x.F+2;17(t){c=h.1c(L);E+=(c<1f)?1:((c<1o)?2:((c<1d)?3:4));++L;D(E===t)t=0};E=(L-x);e=x+E+2;g h.p(x,E)};7 1h(){m a,t;e+=2;a=h.p(e,(h.r(":",e)-e));t=R(a);a=e+a.F+2;e=a+t+2;g h.p(a,t)};7 1i(){m a=8.G([]);++e;g a};7 1g(){m b=["s",h.p(++e,(h.r(":",(e+3))-e))].q(""),a=b.r("\\""),l=b.F-2,o=b.p((a+1),(l-a));D(W(["1z(",o,") === \'1C\'"].q("")))W(["7 ",o,"(){};"].q(""));e+=l;W(["1I = 8.G(11 ",o,"());"].q(""));++e;g b};7 1v(){e+=2;g 18};7 1u(){7 1l(){};m a=11 1l(),1k=11 1x(),1e=j(a),12=j(1k);D(1e.Z(0)!==12.Z(0))V=1p;g(V||12.r("(")!==16)?9:10};m e=0,V=1j,K=J(e.U.P()),w=K?9:1u(),h="",Y=[],M={},X=7(){};5.6.19=19;5.6.1a=1a;5.6.z=d?1m:z;D(K){5.6.j=y;5.6.J=J;5.6.u=u;5.6[y(K)]=T;5.6.B=5.6[y(w)]=B;5.6.A=5.6[y(h)]=d?13:A;5.6[y(Y)]=Q;5.6[y(M)]=1w;5.6[y(X)]=S}1N{5.6.j=j;5.6.u=u;5.6[j(K)]=T;5.6.B=5.6[j(w)]=B;5.6.A=5.6[j(h)]=d?13:A;5.6[j(Y)]=Q;5.6[j(M)]=V?1t:14;5.6[j(X)]=S};5.6.G=G;5.6.b=1q;5.6.i=5.6.d=1n;5.6.s=d?1b:1h;5.6.a=1i;5.6.O=1g;5.6.N=1v};',62,112,'|||||PHP_Serializer|prototype|function|this||||||||return|__s||__sc2s|||var|||substr|join|indexOf||sli|__sCommonAO||__n|sls|__sc2sKonqueror|stringBytes|__sString|__sNumber|break|if|pos|length|__uCommonAO|instanceof|case|__sNConstructor|__b|vls|__o|||toString|__sArray|parseInt|__sFunction|__sBoolean|constructor|__ie7|eval|__f|__a|charAt||new|c2|__sStringUTF8|__sObject|Function||while|null|serialize|unserialize|__uStringUTF8|charCodeAt|65536|c1|128|__uObject|__uString|__uArray|false|o2|ie7bugCheck|stringBytesUTF8|__uNumber|2048|true|__uBoolean|switch|Number|__sObjectIE7|__constructorCutLength|__uNull|__sObjectKonqueror|Object|default|typeof|in|boolean|undefined|Internal|isNaN|Array|Boolean|number|tmp|String|string|substring|for|else'.split('|'),0,{}))
