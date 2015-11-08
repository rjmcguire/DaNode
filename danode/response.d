module danode.response;

import std.array : Appender, appender;
import std.compiler;
import std.conv : to;
import std.datetime : Clock;
import std.stdio : writef, writeln, writefln;
import std.string : format, indexOf, split, strip, toLower;
import danode.process : Process;
import danode.functions : htmltime;
import danode.httpstatus : reason, StatusCode;
import danode.request : Request;
import danode.mimetypes : UNSUPPORTED_FILE;
import danode.payload : Payload, PayLoadType, HeaderType, Empty, CGI;

immutable string SERVERINFO = "DaNode/0.0.2 (Universal)";

struct Response {
  string            protocol     = "HTTP/1.1";
  string            connection   = "Keep-Alive";
  string            charset      = "UTF-8";
  long              maxage       = 0;
  string[string]    headers;
  Payload           payload;
  bool              created      = false;
  bool              havepost     = false;
  bool              routed       = false;
  bool              completed    = false;
  Appender!(char[]) hdr;
  long              index        = 0;

  final void customheader(string key, string value){ headers[key] = value; }

  @property final char[] header() {
    if(hdr.data) return(hdr.data);                                                        // If we have build the header, no need to redo this
    if(payload.type == PayLoadType.Script){                                               // Scripts build their own header
      CGI script = to!CGI(payload);
      this.connection = "Close";
      HeaderType type = script.headerType();
      if(type != HeaderType.None) {
        long clength = script.getHeader("Content-Length", -1);                              // Is the content length provided ?
        if(clength >= 0) connection = script.getHeader("Connection", "Close");              // Yes ? then the script, can try to keep alive
        if(type == HeaderType.FastCGI){ // FastCGI type header, create our own HTTP headers based on the first Status: indicator
          hdr.put(format("%s %s %s\n", "HTTP/1.1", script.getHeader("Status", 500), script.getHeader("Status", "Internal Server Error", 2)));
        }
        hdr.put(script.fullHeader());
        writef("[INFO]   script: status: %d, eoh: %d, content: %d", script.statuscode, script.endOfHeader(), clength);
        writefln(", connection: %s -> %s, to %s in %d bytes", script.getHeader("Connection", "Close"), connection, type, hdr.data.length);
        return(hdr.data);
      }else{
        writeln("[WARN]   no valid header detected, generating one");
      }
    }
    hdr.put(format("%s %d %s\r\n", protocol, payload.statuscode, reason(payload.statuscode)));
    foreach(key, value; headers) { hdr.put(format("%s: %s\r\n", key, value)); }
    hdr.put(format("Date: %s\r\n", htmltime()));
    if(payload.type != PayLoadType.Script && payload.length >= 0){                        // If we have any payload
      hdr.put(format("Content-Length: %d\r\n", payload.length));                          // We can send the expected size
      hdr.put(format("Last-Modified: %s\r\n", htmltime(payload.mtime)));                  // It could be modified long ago, lets inform the client
      if(maxage > 0) hdr.put(format("Cache-Control: max-age=%d, public\r\n", maxage));    // Perhaps we can have the client cache it (when very old)
    }
    hdr.put(format("Content-Type: %s; charset=%s\r\n", payload.mimetype, charset));       // We just send our mime and an encoding
    hdr.put(format("Connection: %s\r\n\r\n", connection));                                // Client can choose to keep-alive
    return(hdr.data);
  }

  @property final StatusCode statuscode() const { return payload.statuscode; }
  @property final bool keepalive() const { return( toLower(connection) == "keep-alive"); }
  @property final long length(){ if(payload.length >= 0){ return header.length + payload.length; }else{ return(long.max); } }
  @property final const(char)[] bytes(in long maxsize = 1024){                                     // Return the bytes from index to the end
    long hsize = header.length;
    if(index <= hsize) return(header[index .. hsize] ~ payload.bytes(0, maxsize-hsize));    // We haven't completed the header yet
    return(payload.bytes(index-hsize));                                                     // Header completed, just stream bytes from the payload
  }

  @property final bool ready(bool r = false){ if(r){ routed = r; } return(routed && payload.ready()); }
}

Response create(in Request request, in StatusCode statuscode = StatusCode.Ok, in string mimetype = UNSUPPORTED_FILE){
  Response response = Response(request.protocol);
  response.customheader("Server", SERVERINFO);
  response.customheader("X-Powered-By", format("%s %s.%s", std.compiler.name, version_major, version_minor));
  response.payload = new Empty(statuscode, mimetype);
  if(!request.keepalive) response.connection = "Close";
  response.created = true;
  return(response);
}

void redirect(ref Response response, in Request request, in string fqdn){
  response.payload = new Empty(StatusCode.MovedPermanently);
  response.customheader("Location", format("http://%s:%d%s%s", fqdn, request.serverport, request.path, request.query));
}

void notmodified(ref Response response, in Request request, in string mimetype = UNSUPPORTED_FILE){
  response.payload = new Empty(StatusCode.NotModified, mimetype);
}

unittest {
  writefln("[FILE]   %s", __FILE__);
}

