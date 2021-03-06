module danode.log;

import std.array, std.stdio, std.string, std.conv, std.datetime, std.file, std.math;
import danode.interfaces : ClientInterface;
import danode.request : Request;
import danode.response : Response;
import danode.functions;
import danode.httpstatus;

extern(C) __gshared int cverbose;         // Verbose level of C-Code

immutable int NOTSET = -1, NORMAL = 0, INFO = 1, DEBUG = 2;

struct Info {
  long[StatusCode]      responses;
  Appender!(long[])     starttimes;
  Appender!(long[])     timings;
  Appender!(bool[])     keepalives;
  long[string]          useragents;
  long[string]          ips;

  string toString(){ return(format("%s %s %s %s %s %s", responses, starttimes.data, timings.data, keepalives.data, useragents, ips)); }
}

class Log {
  private:
    string          path        = "requests.log";
    int             level       = NORMAL;
    File            requests;

  public:
    Info[string]    statistics;

    this(int verbose = NORMAL, bool overwrite = false){
      this.verbose = verbose;
      if(exists(path) && overwrite){
        writefln("[WARN]   overwriting log: %s", path); 
        remove(path);
      }
      requests = File(path, "a");
    }

    @property int verbose() const { return(level); }
    @property int verbose(int verbose = NOTSET){
      if(verbose != NOTSET) {
        if(verbose >= INFO) writefln("[INFO]   Changing verbose level from %s to %s", level, verbose);
        level = verbose;
        cverbose = verbose;
      }
      return(level); 
    }

    void write(in ClientInterface cl, in Request rq, in Response rs){
      string    key = format("%s%s", rq.shorthost, rq.uripath);
      if(!statistics.has(key)) statistics[key] = Info();    // Unknown key, create new Info statistics object
      // Fill run-time statistics
      statistics[key].responses[rs.statuscode]++;
      statistics[key].starttimes.put(rq.starttime.toUnixTime());
      statistics[key].timings.put(Msecs(rq.starttime));
      statistics[key].keepalives.put(rs.keepalive);
      statistics[key].ips[((rq.track)? cl.ip : "DNT")]++;

      if(level >= INFO)
        requests.writefln("[%d]    %s %s:%s %s%s %s %s", rs.statuscode, htmltime(), cl.ip, cl.port, rq.shorthost, rq.uri, Msecs(rq.starttime), rs.payload.length);

      // Write the request to the requests file
      requests.flush();
    }
}


