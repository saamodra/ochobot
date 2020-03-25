<?php

namespace App\Http\Controllers;

use App\Gateway\EventLogGateway;
use App\Gateway\MatkulGateway;
use App\Gateway\TugasGateway;
use App\Gateway\UserGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\Logger;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

class Webhook extends Controller {
    /**
     * @var LINEBot
     */
    private $bot;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var EventLogGateway
     */
    private $logGateway;
    /**
     * @var UserGateway
     */
    private $userGateway;
    /**
     * @var MatkulGateway
     */
    private $matkulGateway;
    /**
     * @var TugasGateway
     */
    private $tugasGateway;
    /**
     * @var array
     */
    private $user;

    public function __construct(
        Request $request,
        Response $response,
        Logger $logger,
        EventLogGateway $logGateway,
        UserGateway $userGateway,
        MatkulGateway $matkulGateway,
        TugasGateway $tugasGateway
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->logGateway = $logGateway;
        $this->userGateway = $userGateway;
        $this->matkulGateway = $matkulGateway;
        $this->tugasGateway = $tugasGateway;

        // create bot object
        $httpClient = new CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
    }

    public function __invoke() {
        //get request
        $body = $this->request->all();

        //debugging data
        $this->logger->debug('Body', $body);

        //save log
        $signature = $this->request->server('HTTP_X_LINE_SIGNATURE') ?: '-';
        $this->logGateway->saveLog($signature, json_encode($body, true));

        return $this->handleEvents();
    }

    private function handleEvents()
    {
        $data = $this->request->all();
    
        if(is_array($data['events'])){
            foreach ($data['events'] as $event)
            {
                // skip group and room event
                $this->user = $this->userGateway->getUser($event['source']['userId']);
                if($event['source']['type'] == 'group') {
                    if($event['type'] == 'join') {
                        $this->greetingMessage($event['source']['groupId']);
                    } else {
                        if(!$this->user) $this->followCallback($event);
                        else {
                            // respond event
                            if($event['type'] == 'message'){
                                if(method_exists($this, $event['message']['type'].'Message')){
                                    $this->{$event['message']['type'].'Message'}($event);
                                }
                            } else {
                                if(method_exists($this, $event['type'].'Callback')){
                                    $this->{$event['type'].'Callback'}($event);
                                }
                            }
                        }
                    }
                } else if($event['source']['type'] == 'room') {
                    $this->greetingMessage($event['source']['groupId']);
        
                    // if user not registered
                    if(!$this->user) $this->followCallback($event);
                    else {
                        // respond event
                        if($event['type'] == 'message'){
                            if(method_exists($this, $event['message']['type'].'Message')){
                                $this->{$event['message']['type'].'Message'}($event);
                            }
                        } else {
                            if(method_exists($this, $event['type'].'Callback')){
                                $this->{$event['type'].'Callback'}($event);
                            }
                        }
                    }
                } else {
        
                    // if user not registered
                    if(!$this->user) $this->followCallback($event);
                    else {
                        // respond event
                        if($event['type'] == 'message'){
                            if(method_exists($this, $event['message']['type'].'Message')){
                                $this->{$event['message']['type'].'Message'}($event);
                            }
                        } else {
                            if(method_exists($this, $event['type'].'Callback')){
                                $this->{$event['type'].'Callback'}($event);
                            }
                        }
                    }
                }
    
            }
        }
    
    
        $this->response->setContent("No events found!");
        $this->response->setStatusCode(200);
        return $this->response;
    }

    private function greetingMessage($eventId) {
        $getprofile = $this->bot->getProfile($eventId);
        $profile = $getprofile->getJSONDecodedBody();
        $message = "Halo, " . $profile['displayName'] . "!\n";
        $message .= "Ochobot bisa menampilkan tugas-tugas SI 19 lho. Coba tekan tombol \"Mata Kuliah\" untuk melihat mata kuliah dan \"Semua Tugas\" untuk melihat semua tugas.";
        

        $buttonsTemplate = new ButtonTemplateBuilder(
            null,
            $message,
            null,
            [
                new MessageTemplateActionBuilder("Mata Kuliah", "Mata Kuliah"), 
                new MessageTemplateActionBuilder("Semua Tugas", "Tugas")
            ]
        );


        //create sticker message
        $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002768);

        // merge all message
        $multiMesssageBuilder = new MultiMessageBuilder();
        $multiMesssageBuilder->add($stickerMessageBuilder);
        $multiMesssageBuilder->add(new TemplateMessageBuilder('Home', $buttonsTemplate));

        $result = $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
    }

    private function followCallback($event) {
        $res = $this->bot->getProfile($event['source']['userId']);
        if($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();

            //create welcome message
            $message = "Salam kenal, " . $profile['displayName'] . "!\n";
            $message .= "Ochobot bisa menampilkan tugas-tugas SI 19 lho. Coba tekan tombol \"Mata Kuliah\" untuk melihat mata kuliah dan \"Semua Tugas\" untuk melihat semua tugas.";
            $textMessageBuilder = new TextMessageBuilder($message);
            
    
            $buttonsTemplate = new ButtonTemplateBuilder(
                null,
                $message,
                null,
                [
                    new MessageTemplateActionBuilder("Mata Kuliah", "Mata Kuliah"), 
                    new MessageTemplateActionBuilder("Semua Tugas", "Tugas")
                ]
            );


            //create sticker message
            $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002768);

            // merge all message
            $multiMesssageBuilder = new MultiMessageBuilder();
            $multiMesssageBuilder->add($stickerMessageBuilder);
            $multiMesssageBuilder->add(new TemplateMessageBuilder('Home', $buttonsTemplate));


            // send reply message
            $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);

            // save user data
            $this->userGateway->saveUser(
                $profile['userId'],
                $profile['displayName']
            );
        }
    }

    

    private function textMessage($event) {
        $userMessage = $event['message']['text'];
        if($this->user['state'] == 0) {
            if(strtolower($userMessage) == "mata kuliah") {
                $matkul = array();
                foreach($this->matkulGateway->getAllMatkul() as $t) {
                    $matkul[] = new CarouselColumnTemplateBuilder(
                            $t->nama_matkul, 
                            "Semester ".$t->semester." - ".$t->tahun_ajaran,
                            $t->image, 
                            [
                                new UriTemplateActionBuilder('Buka E-Learning', $t->link_matkul),
                                new MessageTemplateActionBuilder("Lihat Tugas", "Lihat Tugas ".$t->id_matkul),
                            ]
                        );
                }
                
                $carouselMatkul = new CarouselTemplateBuilder($matkul);
                $this->userGateway->setUserState($this->user['user_id'], 1);

                $templateMessage = new TemplateMessageBuilder('CarouselMatkul', $carouselMatkul);
                $this->bot->replyMessage($event['replyToken'], $templateMessage);
            } else if(strtolower($userMessage) == "tugas") {
                $matkul = "";
                foreach($this->tugasGateway->getAllTugas() as $t) {
                    $matkul = $t->nama_matkul;
                    $tugas[] = new CarouselColumnTemplateBuilder(
                            $t->judul, 
                            $t->nama_matkul." - Semester ". $t->semester." - ".$t->tahun_ajaran,
                            $t->image, 
                            [
                                new UriTemplateActionBuilder('Buka E-Learning', $t->link_matkul),
                                new UriTemplateActionBuilder('Buka Modul Soal', $t->link_modul),
                                new MessageTemplateActionBuilder("Kembali", "Kembali"),
                            ]
                        );
                
                }

                $carouselTugas = new CarouselTemplateBuilder($tugas);

                $templateMessage = new TemplateMessageBuilder('Tugas '.$matkul, $carouselTugas);
                $this->bot->replyMessage($event['replyToken'], $templateMessage);
            } else {
                $message = 'Silahkan kirim pesan "Mata Kuliah" untuk melihat mata kuliah dan "Semua Tugas" untuk melihat semua tugas.';
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);

            }

        } else if($this->user['state'] == 1) {
            if(substr(strtolower($userMessage),0, 11) == "lihat tugas") {                
                $msg = array();
                $msg = explode(" ", $userMessage);
                $idMatkul = end($msg);
                $tugas = array();
                $matkul = "";
                
                foreach($this->tugasGateway->getTugasMatkul($idMatkul) as $t) {
                    $matkul = $t->nama_matkul;
                    $tugas[] = new CarouselColumnTemplateBuilder(
                            $t->judul, 
                            $t->nama_matkul." - Semester ". $t->semester." - ".$t->tahun_ajaran,
                            $t->image, 
                            [
                                new UriTemplateActionBuilder('Buka E-Learning', $t->link_matkul),
                                new UriTemplateActionBuilder('Buka Modul Soal', $t->link_modul),
                                new MessageTemplateActionBuilder("Kembali", "Kembali"),
                            ]
                        );
                
                }

                $carouselTugas = new CarouselTemplateBuilder($tugas);

                $templateMessage = new TemplateMessageBuilder('Tugas '.$matkul, $carouselTugas);
                $this->bot->replyMessage($event['replyToken'], $templateMessage);
            } else if(strtolower($userMessage) == "kembali") {
                $this->userGateway->setUserState($this->user['user_id'], 0);
                $message = "Apa yang bisa Ochobot lakukan?";
                $buttonsTemplate = new ButtonTemplateBuilder(
                    null,
                    $message,
                    null,
                    [
                        new MessageTemplateActionBuilder("Mata Kuliah", "Mata Kuliah"), 
                        new MessageTemplateActionBuilder("Semua Tugas", "Tugas")
                    ]
                );

                $templateMessage = new TemplateMessageBuilder('Home', $buttonsTemplate);
                $this->bot->replyMessage($event['replyToken'], $templateMessage);
            } else {
                
                $message = "Keyword yg anda masukkan salah!";
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);

            }
        } 
    }
}