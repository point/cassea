// formatDate :
// a PHP date like function, for formatting date strings
// authored by Svend Tofte <www.svendtofte.com>
// the code is in the public domain
//
// see http://www.svendtofte.com/code/date_format/
// and http://www.php.net/date
//
// thanks to 
//  - Daniel Berlin <mail@daniel-berlin.de>,
//    major overhaul and improvements
//  - Matt Bannon,
//    correcting some stupid bugs in my days-in-the-months list!
//
// input : format string
// time : epoch time (seconds, and optional)
//
// if time is not passed, formatting is based on 
// the current "this" date object's set time.
//
// supported switches are
// a, A, B, c, d, D, F, g, G, h, H, i, I (uppercase i), j, l (lowecase L), 
// L, m, M, n, N, O, P, r, s, S, t, U, w, W, y, Y, z, Z
// 
// unsupported (as compared to date in PHP 5.1.3)
// T, e, o
eval(function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('Q.1O.1j=C(e,f){9 k=["2b","2c","2d","27","2e","2g","2h"];9 l=["2i","2a","2f","25","1W","20","1S"];9 o=["1T","1U","1Q","1V","1t","1X","1Y","1Z","2j","26","2k","2m"];9 p=["1c","2u","2v","2w","1t","2s","2x","2z","2A","2B","2t","2y"];9 q={a:C(){8 s.15()>11?"2r":"2p"},A:C(){8(v.a().2n())},B:C(){9 a=(s.14()+X)*X;9 b=(s.15()*2o)+(s.1q()*X)+s.1s()+a;9 c=10.18(b/2l.4);E(c>17)c-=17;E(c<0)c+=17;E((K(c)).J==1)c="R"+c;E((K(c)).J==2)c="0"+c;8 c},c:C(){8(v.Y()+"-"+v.m()+"-"+v.d()+"T"+v.h()+":"+v.i()+":"+v.s()+v.P())},d:C(){9 j=K(v.j());8(j.J==1?"0"+j:j)},D:C(){8 l[s.1b()]},F:C(){8 p[s.1f()]},g:C(){8 s.15()>12?s.15()-12:s.15()},G:C(){8 s.15()},h:C(){9 g=K(v.g());8(g.J==1?"0"+g:g)},H:C(){9 G=K(v.G());8(G.J==1?"0"+G:G)},i:C(){9 a=K(s.1q());8(a.J==1?"0"+a:a)},I:C(){9 a=16 Q("1c 1 "+v.Y()+" R:R:R");8(a.14()==s.14()?0:1)},j:C(){8 s.1o()},l:C(){8 k[s.1b()]},L:C(){9 Y=v.Y();E((Y%4==0&&Y%1g!=0)||(Y%4==0&&Y%1g==0&&Y%2q==0)){8 1}19{8 0}},m:C(){9 n=K(v.n());8(n.J==1?"0"+n:n)},M:C(){8 o[s.1f()]},n:C(){8 s.1f()+1},N:C(){9 w=v.w();8(w==0?7:w)},O:C(){9 a=10.1R(s.14());9 h=K(10.18(a/X));9 m=K(a%X);h.J==1?h="0"+h:1;m.J==1?m="0"+m:1;8 s.14()<0?"+"+h+m:"-"+h+m},P:C(){9 O=v.O();8(O.1k(0,3)+":"+O.1k(3,2))},r:C(){9 r;r=v.D()+", "+v.d()+" "+v.M()+" "+v.Y()+" "+v.H()+":"+v.i()+":"+v.s()+" "+v.O();8 r},s:C(){9 a=K(s.1s());8(a.J==1?"0"+a:a)},S:C(){1P(s.1o()){13 1:8("1i");13 2:8("1m");13 3:8("1n");13 21:8("1i");13 22:8("1m");13 23:8("1n");13 V:8("1i");1v:8("1w")}},t:C(){9 a=[1z,V,28,V,1a,V,1a,V,V,1a,V,1a,V];E(v.L()==1&&v.n()==2)8 29;8 a[v.n()]},U:C(){8 10.1F(s.1h()/17)},w:C(){8 s.1b()},W:C(){9 a=v.N();9 b=v.z();9 c=1A+v.L()-b;E(c<=2&&a<=(3-c)){8 1}E(b<=2&&a>=5){8 16 Q(v.Y()-1,11,V).1j("W")}9 d=16 Q(v.Y(),0,1).1b();d=d!=0?d-1:6;E(d<=3){8(1+10.18((b+d)/7))}19{8(1+10.18((b-(7-d))/7))}},y:C(){9 y=K(v.Y());8 y.1B(y.J-2,y.J)},Y:C(){E(s.1d){9 a=16 Q("1c 1 1r R:R:R +1D");9 x=a.1d();E(x==1r){8 s.1d()}}9 x=s.1K();9 y=x%1g;y+=(y<1J)?1G:1M;8 y},z:C(){9 t=16 Q("1c 1 "+v.Y()+" R:R:R");9 a=s.1h()-t.1h();8 10.18(a/17/X/X/24)},Z:C(){8(s.14()*-X)}}C 1p(a){E(q[a]!=1C){8 q[a]()}19{8 a}}9 s;E(f){9 s=16 Q(f)}19{9 s=v}9 u=e.1E("");9 i=0;1H(i<u.J){E(u[i]=="\\\\"){u.1I(i,1)}19{u[i]=1p(u[i])}i++}8 u.1N("")}Q.1L="Y-m-d\\\\1e:i:1l";Q.1u="Y-m-d\\\\1e:i:1y";Q.1x="D, d M Y H:i:s O";Q.2C="Y-m-d\\\\1e:i:1l";',62,163,'||||||||return|var||||||||||||||||||||||this|||||||function||if|||||length|String||||||Date|00||||31||60|||Math|||case|getTimezoneOffset|getHours|new|1000|floor|else|30|getDay|January|getFullYear|TH|getMonth|100|getTime|st|formatDate|substr|sP|nd|rd|getDate|getSwitch|getMinutes|2001|getSeconds|May|DATE_ISO8601|default|th|DATE_RFC2822|sO|null|364|substring|undefined|0000|split|round|2000|while|splice|38|getYear|DATE_ATOM|1900|join|prototype|switch|Mar|abs|Sat|Jan|Feb|Apr|Thu|Jun|Jul|Aug|Fri|||||Wed|Oct|Wednesday|||Mon|Sunday|Monday|Tuesday|Thursday|Tue|Friday|Saturday|Sun|Sep|Nov|86|Dec|toUpperCase|3600|am|400|pm|June|November|February|March|April|July|December|August|September|October|DATE_W3C'.split('|'),0,{}))
