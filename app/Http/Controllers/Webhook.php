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

                if($event['source']['type'] == 'group') {
                    if($event['type'] == 'join') {
                        $this->greetingMessage($event);
                    } else if($event['type'] == 'leave') {

                    }else {
                        $this->groupMessage($event);

                    }
                } else if($event['source']['type'] == 'room') {
                    $this->greetingMessage($event);
                    $this->user = $this->userGateway->getUser($event['source']['userId']);

                    $this->handleOneOnOneChats($event);
                } else {
                    $this->user = $this->userGateway->getUser($event['source']['userId']);

                    $this->handleOneOnOneChats($event);
                }

            }
        }


        $this->response->setContent("No events found!");
        $this->response->setStatusCode(200);
        return $this->response;
    }

    private function handleOneOnOneChats($event) {
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

    private function greetingMessage($event) {
        $message = "Hai, semua! Ochobot bisa menampilkan tugas-tugas SI 19 lho. Cukup tekan tombol \"Lihat Tugas\" dibawah ini ya";
        $buttonsTemplate = new ButtonTemplateBuilder(
            null,
            $message,
            null,
            [
                new MessageTemplateActionBuilder("Lihat Tugas", "Ochobot Lihat Tugas")
            ]
        );


        $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002738);

        // merge all message
        $multiMesssageBuilder = new MultiMessageBuilder();
        $multiMesssageBuilder->add($stickerMessageBuilder);
        $multiMesssageBuilder->add(new TemplateMessageBuilder('Home', $buttonsTemplate));
        $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
    }

    private function groupMessage($event) {
        $userMessage = $event['message']['text'];
        if(strtolower($userMessage) == "ochobot lihat tugas") {
            foreach($this->tugasGateway->getAllTugas() as $t) {
                $tugas[] = new CarouselColumnTemplateBuilder(
                    $t->judul,
                    "Deadline : ".$this->tugasGateway->datedifference(strtotime($t->due_date))."\n* ".date("d M Y h:i", strtotime($t->due_date)),
                    $t->matkul->image,
                    [
                        new UriTemplateActionBuilder('Buka E-Learning', $t->matkul->link_matkul),
                        new UriTemplateActionBuilder('Buka Modul Soal', $t->link_modul),
                        new MessageTemplateActionBuilder("Terimakasih Ochobot", "Terimakasih Ochobot!"),
                    ]
                );
            }

            if(empty($tugas)) {
                $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002761);

                // merge all message
                $buttonsTemplate = new ButtonTemplateBuilder(
                    null,
                    "Hari ini nggak ada tugas nih, rebahan kuy!",
                    null,
                    [
                        new MessageTemplateActionBuilder("Terima Kasih Ochobot", "Terimakasih Ochobot!"),
                    ]
                );
                $multiMesssageBuilder = new MultiMessageBuilder();
                $multiMesssageBuilder->add($stickerMessageBuilder);
                $multiMesssageBuilder->add(new TemplateMessageBuilder('Tugas', $buttonsTemplate));
            } else {
                $carouselTugas = new CarouselTemplateBuilder($tugas);

                $multiMesssageBuilder = new TemplateMessageBuilder('Tugas '.date('d-m-Y'), $carouselTugas);

            }
            $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
        } else if(strtolower($userMessage) == "terimakasih ochobot!") {
            $result = $this->bot->getProfile($event['source']['userId']);
            $profile = $result->getJSONDecodedBody();
            $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626501);

            // merge all message
            $textMessageBuilder = new TextMessageBuilder("Iya, sama-sama ".$profile['displayName']."!");
            $multiMesssageBuilder = new MultiMessageBuilder();
            $multiMesssageBuilder->add($stickerMessageBuilder);
            $multiMesssageBuilder->add($textMessageBuilder);

            $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
        } else {
            $textMessageBuilder = new TextMessageBuilder("Keyword salah ya!");
            $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
        }
    }

    private function leaveCallback($event) {
        // $res = $this->bot->getProfile($event['source']['userId']);

    }

    private function unfollowCallback($event) {
        $this->userGateway->setUserState($this->user['user_id'], 0);
    }

    private function followCallback($event) {
        $res = $this->bot->getProfile($event['source']['userId']);
        if($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();

            //create welcome message
            $message = "Salam kenal, " . $profile['displayName'] . "!\nTekan tombol \"Mata Kuliah\" untuk melihat daftar mata kuliah dan tekan tombol \"Lihat Tugas\" untuk melihat daftar tugas hari ini.";
            $buttonsTemplate = new ButtonTemplateBuilder(
                null,
                $message,
                null,
                [
                    new MessageTemplateActionBuilder("Mata Kuliah", "Mata Kuliah"),
                    new MessageTemplateActionBuilder("Semua Tugas", "Tugas")
                ]
            );


            $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002738);

            $this->sendButtonSticker($event, $stickerMessageBuilder, $buttonsTemplate);

            // save user data
            $this->userGateway->saveUser(
                $profile['userId'],
                $profile['displayName']
            );
        }
    }

    private function CarouselTugas($event, $arrTugas, $matkul = null) {
        $tugas = array();
        foreach($arrTugas as $t) {
            // $tugas[] = new CarouselColumnTemplateBuilder(
            //     $t->judul,
            //     "Deadline : ".$this->tugasGateway->datedifference(strtotime($t->due_date))."\n* ".date("d M Y h:i", strtotime($t->due_date)),
            //     $t->matkul->image,
            //     [
            //         new UriTemplateActionBuilder('Buka E-Learning', $t->matkul->link_matkul),
            //         new UriTemplateActionBuilder('Buka Modul Soal', $t->link_modul),
            //         // new MessageTemplateActionBuilder("Terima Kasih Ochobot", "Terimakasih Ochobot!"),
            //     ]
            // );

            $tugas[] = new CarouselColumnTemplateBuilder(
                $t->judul,
                "Deadline : asasd",
                $t->matkul->image,
                [
                    new UriTemplateActionBuilder('Buka E-Learning', $t->matkul->link_matkul),
                    new UriTemplateActionBuilder('Buka Modul Soal', $t->link_modul),
                ]
            );

            // $matkul[] = new CarouselColumnTemplateBuilder(
            //     $t->nama_matkul,
            //     "Semester ".$t->semester->semester." - ".$t->semester->tahun_ajaran,
            //     $t->image,
            //     [
            //         new UriTemplateActionBuilder('Buka E-Learning', $t->link_matkul),
            //         new MessageTemplateActionBuilder("Lihat Tugas", "Lihat Tugas ".$t->nama_matkul),
            //     ]
            // );
        }

        if(empty($tugas)) {
            $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002761);

            // merge all message
            $buttonsTemplate = new ButtonTemplateBuilder(
                null,
                "Hari ini ".$matkul." tidak ada tugas.",
                null,
                [
                    new MessageTemplateActionBuilder("Terima Kasih Ochobot", "Terimakasih Ochobot!"),
                ]
            );
            $multiMesssageBuilder = new MultiMessageBuilder();
            $multiMesssageBuilder->add($stickerMessageBuilder);
            $multiMesssageBuilder->add(new TemplateMessageBuilder('Tugas '.$matkul, $buttonsTemplate));
        } else {
            $carouselTugas = new CarouselTemplateBuilder($tugas);

            $multiMesssageBuilder = new TemplateMessageBuilder('Tugas '.date('d-m-Y'), $carouselTugas);
        }

        $this->userGateway->setThxState($this->user['user_id'], 0);
        $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
    }

    private function sendMsgSticker($event, $sticker, $message) {
        $textMessageBuilder = new TextMessageBuilder($message);
        $multiMesssageBuilder = new MultiMessageBuilder();
        $multiMesssageBuilder->add($sticker);
        $multiMesssageBuilder->add($textMessageBuilder);
        $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
    }

    private function sendButtonSticker($event, $sticker, $button) {
        // merge all message
        $multiMesssageBuilder = new MultiMessageBuilder();
        $multiMesssageBuilder->add($sticker);
        $multiMesssageBuilder->add(new TemplateMessageBuilder('Home', $button));
        $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
    }

    private function sendButtonMsg($event, $button, $msg) {
        $textMessageBuilder = new TextMessageBuilder($msg);
        $multiMesssageBuilder = new MultiMessageBuilder();
        $multiMesssageBuilder->add($textMessageBuilder);
        $multiMesssageBuilder->add($button);
        $this->bot->replyMessage($event['replyToken'], $multiMesssageBuilder);
    }

    private function textMessage($event) {
        $userMessage = $event['message']['text'];
        if($this->user['state'] == 0) {
            if(strtolower($userMessage) == "mata kuliah") {
                $matkul = array();
                foreach($this->matkulGateway->getAllMatkul() as $t) {
                    $matkul[] = new CarouselColumnTemplateBuilder(
                            $t->nama_matkul,
                            "Semester ".$t->semester->semester." - ".$t->semester->tahun_ajaran,
                            $t->image,
                            [
                                new UriTemplateActionBuilder('Buka E-Learning', $t->link_matkul),
                                new MessageTemplateActionBuilder("Lihat Tugas", "Lihat Tugas ".$t->nama_matkul),
                            ]
                        );
                }

                $carouselMatkul = new CarouselTemplateBuilder($matkul);
                $this->userGateway->setUserState($this->user['user_id'], 1);

                $templateMessage = new TemplateMessageBuilder('Daftar Matkul', $carouselMatkul);
                $this->bot->replyMessage($event['replyToken'], $templateMessage);
            } else if(strtolower($userMessage) == "tugas") {
                $this->userGateway->setUserState($this->user['user_id'], 1);
                $this->CarouselTugas($event, $this->tugasGateway->getAllTugas());
            } else {
                if($this->user['thx'] == 1) {
                    $message = 'Silahkan kirim pesan "Mata Kuliah" untuk melihat mata kuliah dan "Tugas" untuk melihat semua tugas, okay?';
                    $stickerMessageBuilder = new StickerMessageBuilder(11537, 52002735);

                    $this->sendMsgSticker($event, $stickerMessageBuilder, $message);
                } else {
                    $message = "Makasihnya manaaa!";
                    $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626532);

                    // merge all message
                    $this->sendMsgSticker($event, $stickerMessageBuilder, $message);
                }


            }

        } else if($this->user['state'] == 1) {
            if(substr(strtolower($userMessage),0, 11) == "lihat tugas") {
                $msg = array();
                $msg = explode(" ", $userMessage);
                $nama_matkul = substr($userMessage, 12);
                $tugas = array();
                $idMatkul = $this->tugasGateway->getIdMatkul($nama_matkul);

                $tugasMatkul = $this->tugasGateway->getTugasMatkul(intval($idMatkul));

                $this->CarouselTugas($event, $tugasMatkul, $nama_matkul);

            } else if(trim(substr(strtolower($userMessage),0, 11)) == "terimakasih") {
                $this->userGateway->setUserState($this->user['user_id'], 0);
                $this->userGateway->setThxState($this->user['user_id'], 1);

                $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626501);

                $message = "Apakah ada lagi yang bisa Ochobot lakukan?";
                $buttonsTemplate = new ButtonTemplateBuilder(
                    null,
                    $message,
                    null,
                    [
                        new MessageTemplateActionBuilder("Mata Kuliah", "Mata Kuliah"),
                        new MessageTemplateActionBuilder("Semua Tugas", "Tugas")
                    ]
                );


                $this->sendButtonSticker($event, $stickerMessageBuilder, $buttonsTemplate);

            } else {
                if($this->user['thx'] == 1) {
                    $message = "Keyword yg anda masukkan salah! Silahkan kirim pesan \"Lihat Tugas <Nama Matkul>\" untuk melihat tugas.'";
                    $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626530);

                    // merge all message
                    $this->sendMsgSticker($event, $stickerMessageBuilder, $message);
                } else {
                    $message = "Makasihnya manaaa!";
                    $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626532);
                    // merge all message
                }
                $this->sendMsgSticker($event, $stickerMessageBuilder, $message);
            }
        } else {
            $message = "Keyword yang anda masukkan salah!";
            $stickerMessageBuilder = new StickerMessageBuilder(11538, 51626530);

            // merge all message
            $this->sendMsgSticker($event, $stickerMessageBuilder, $message);
        }
    }
}