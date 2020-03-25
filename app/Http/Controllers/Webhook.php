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
                if(! isset($event['source']['userId'])) continue;
    
                // get user data from database
                $this->user = $this->userGateway->getUser($event['source']['userId']);
    
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
    
    
        $this->response->setContent("No events found!");
        $this->response->setStatusCode(200);
        return $this->response;
    }

    private function followCallback($event) {
        $res = $this->bot->getProfile($event['source']['userId']);
        if($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();

            //create welcome message
            $message = "Salam kenal, " . $profile['displayName'] . "!\n";
            $message .= "Silahkan kirim pesan \"Tugas\" untuk melihat tugas dari Matkul Prodi Sistem Informasi";
            $textMessageBuilder = new TextMessageBuilder($message);

            //create sticker message
            $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002768);


            // merge all message
            $multiMesssageBuilder = new MultiMessageBuilder();
            $multiMesssageBuilder->add($stickerMessageBuilder);
            $multiMesssageBuilder->add($textMessageBuilder);


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
            if(strtolower($userMessage) == "tugas" || strtolower($userMessage) == "kembali") {
                $matkul = array();
                foreach($this->matkulGateway->getAllMatkul() as $t) {
                    $matkul[] = new CarouselColumnTemplateBuilder(
                            $t->nama_matkul, 
                            $t->semester." - ".$t->tahun_ajaran,
                            $t->image, 
                            [
                                new UriTemplateActionBuilder('Buka E-Learning', $t->link_matkul),
                                new MessageTemplateActionBuilder("Lihat Tugas", "Lihat Tugas ".$t->id_matkul),
                            ]
                        );
                }
                
                $carouselTemplateBuilder = new CarouselTemplateBuilder($matkul);
                $this->userGateway->setUserState($this->user['user_id'], 1);

                $templateMessage = new TemplateMessageBuilder('Carousel', $carouselTemplateBuilder);
                $this->bot->replyMessage($event['replyToken'], $templateMessage);
            } else {
                $message = 'Silahkan kirim pesan "Tugas" untuk melihat tugas.';
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);

            }

        } else if($this->user['state'] == 1) {
            if(substr(strtolower($userMessage),0, 11) == "lihat tugas ") {                
                $idMatkul = end(explode("/", $userMessage));
                $tugas = array();
                foreach($this->getTugasMatkul($idMatkul) as $t) {
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

                $this->userGateway->setState($this->user['user_id'], 0);
                $carouselTemplateBuilder = new CarouselTemplateBuilder($matkul);
                $this->userGateway->setState($this->user['user_id'], 1);

                $templateMessage = new TemplateMessageBuilder('Carousel', $carouselTemplateBuilder);
                $this->bot->replyMessage($event['replyToken'], $templateMessage);
            } else {
                
                $message = substr(strtolower($userMessage),0, 11) . " lihat tugas ";
                $textMessageBuilder = new TextMessageBuilder($message);
                $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);

            }
        } 
    }

    private function stickerMessage($event) {
        //create sticker message
        $stickerMessageBuilder = new StickerMessageBuilder(1, 106);

        //create text message
        $message = 'Silahkan kirim pesan "MULAI" untuk memulai kuis.';
        $textMessageBuilder = new TextMessageBuilder($message);

        //merge all message
        $multiMesssageBuilder = new MultiMessageBuilder();
        $multiMesssageBuilder->add($stickerMessageBuilder);
        $multiMesssageBuilder->add($textMessageBuilder);

        //send message
        $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
    }

}