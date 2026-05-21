<?php
namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppController extends Controller
{
    public function sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables)
    {
        $twilioSid = env('TWILIO_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_FROM');

        $twilio = new Client($twilioSid, $twilioToken);

        try {
            $contentVariables = [];
            foreach ($variables as $index => $value) {
                $contentVariables[(string)($index + 1)] = $value ?? ''; // Assign empty string if null
            }

            $message = $twilio->messages->create(
                "whatsapp:$phoneNumber",
                [
                    "from" => $twilioWhatsAppNumber,
                    "contentSid" => $contentTemplateSid,
                    "contentVariables" => json_encode($contentVariables),
                    "statusCallback" => url('/webhook/whatsapp/status'),
                ]
            );

            $messageText = $this->generateMessageFromTemplate($contentTemplateSid, $contentVariables);

            ChatMessage::create([
                'sender' => preg_replace('/^\+|^whatsapp:/', '', env('TWILIO_WHATSAPP_FROM')),
                'receiver' => preg_replace('/^\+|^whatsapp:/', '', $phoneNumber),
                'message' => $messageText,
                'twilio_message_id' => $message->sid,
                'is_from_customer' => false,
            ]);

            return response()->json(['message' => 'WhatsApp template message sent successfully', 'sid' => $message->sid]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Template Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateMessageFromTemplate($contentTemplateSid, $variables)
    {
        $templates = [
            //EN
            'HXe771df50cc3d315ec8cd86321b4ff70d' =>
                "Hi {{1}}. As per discussed via phone call, our demo session has been scheduled.
                Company : {{2}}
                Phone No : {{3}}
                PIC : {{4}}
                Email : {{5}}

                Demo Type : {{6}}
                Demo Date / Time : {{7}}
                Meeting Link : {{8}}",

            'HX5c9b745783710d7915fedc4e7e503da0' =>
                "Hi {{1}}! I'm {{2}} from TimeTec. Thanks for your interest in our HR Cloud Solutions!\n\n" .
                "We offer awesome modules to make HR tasks a breeze:\n" .
                "✅ Time Attendance\n" .
                "✅ Payroll System\n" .
                "✅ Claim Management\n" .
                "✅ Leave Management\n\n" .
                "🎁 Special Promotion:\n" .
                "Secure a FREE Biometric Device when you subscribe to our Time Attendance module (terms and conditions apply)!\n" .
                "Why not schedule a quick demo to see how our solutions can benefit your organization? Plus, I’ll show you how to claim your FREE Biometric Device.\n\n" .
                "🚀 Here’s our brochure to get you started: https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf\n" .
                "Can’t wait to chat with you! 😊",

            'HX6531d9c843b71e0a45accd0ce2cfe5f2' =>
                "Hi {{1}},  {{2}} here again! 😊\n\n" .
                "Just wanted to check in and see if you've had a chance to look over our brochure.\n" .
                "If you're interested in setting up a demo, please let me know the best time to call you so we can arrange it for you.",

            'HXcccb50b8124d29d7d21af628b92522d4' =>
                "Just a quick reminder—the offer for a FREE Biometric Device (terms and conditions apply) is still available!\n" .
                "It’s a great way to enhance your HR capabilities at no extra cost. 😊\n" .
                "If now isn't the right time or if there’s someone else I should reach out to, please let me know. I’m here to assist!",

            'HX517e06b8e7ddabea51aa799bfd1987f8' =>
                "Just popping in one last time to make sure I’m not overloading your WhatsApp. 🙈\n\n" .
                "If now isn’t the right time for a chat, could you let me know when might be better, or if there’s someone else I should reach out to?\n\n" .
                "And hey, if you ever want to revisit this down the line, I’m just a message away and ready to dive back in whenever you are! 😊",

            //BM
            'HXcc05134b6c74ecc02682a25887978630' =>
                "Hai {{1}}! Saya {{2}} daripada TimeTec. Terima kasih atas minat anda terhadap Penyelesaian HR Berasaskan Awan kami!\n\n" .
                "Kami menawarkan modul-modul hebat untuk memudahkan tugasan HR anda:\n" .
                "✅ Pengurusan Kehadiran\n" .
                "✅ Pengurusan Penggajian\n" .
                "✅ Pengurusan Tuntutan\n" .
                "✅ Pengurusan Cuti\n\n" .
                "🎁 Promosi Istimewa:\n" .
                "Dapatkan Peranti Biometrik PERCUMA apabila anda langgan modul Sistem Kehadiran kami (tertakluk kepada terma dan syarat)!\n" .
                "Mari kita jadualkan sesi demo ringkas untuk lihat bagaimana penyelesaian kami boleh memberi manfaat kepada organisasi anda. Saya juga akan tunjukkan cara untuk menebus Peranti Biometrik PERCUMA anda.\n\n" .
                "🚀 Berikut ialah risalah kami untuk bantu anda mula: https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf\n" .
                "Anda boleh WhatsApp saya jika ada sebarang pertanyaan! 😊",

            'HXbb1b933e2fa363c64c996ae0da7c8773' =>
                "Hai {{1}}, {{2}} di sini lagi! 😊\n" .
                "Cuma nak tanya kalau anda sempat tengok risalah yang kami kongsikan sebelum ni?\n" .
                "Jika anda berminat untuk menetapkan sesi demo, anda boleh maklumkan masa yang sesuai untuk kami hubungi dan kami boleh aturkannya untuk anda.",

            'HX8094ffaa4380226a4c803c10ea59655e' =>
                "Sekadar peringatan ringkas—tawaran untuk mendapatkan Peranti Biometrik PERCUMA masih ada (tertakluk kepada terma dan syarat)!\n" .
                "Ini adalah peluang terbaik untuk meningkatkan keupayaan HR anda tanpa sebarang kos tambahan. 😊\n" .
                "Jika ini bukan masa yang sesuai, atau sekiranya ada individu lain yang patut saya hubungi, sila maklumkan kepada saya. Saya sedia membantu!",

            'HX4d2db45f7de1fd07563369d87a0c8c75' =>
                "Maaf kerana mengganggu, saya cuma nak pastikan mesej saya sebelum ini tak tenggelam di WhatsApp anda.\n" .
                "Sekiranya waktu ini kurang sesuai untuk berbual, mohon maklumkan waktu yang lebih sesuai, atau jika ada individu lain yang lebih berkaitan untuk saya hubungi.\n" .
                "Sekiranya anda ingin berbincang semula pada masa akan datang, saya sentiasa bersedia untuk membantu. Cukup sekadar hantarkan mesej, dan saya akan bantu sebaik mungkin. 😊",

            //CN
            'HXbd3b09adc6ec254a63b9456984945357' =>
                "您好！我是TimeTec的{{1}}。非常感谢您对我们HR云端系统的关注！\n\n" .
                "我们提供一系列超实用的HR模块，协助您轻松应对人事管理：\n" .
                "✅考勤系统\n" .
                "✅薪资系统\n" .
                "✅报销系统\n" .
                "✅休假系统\n\n" .
                "🎁限时优惠：\n" .
                "现在订阅我们的考勤系统，即可免费获得生物识别设备！（需符合条款与条件）\n\n" .
                "立即预约系统演示，我们将向您展示我们的系统如何优化HR流程，我们也会说明如何领取您的免费设备。\n\n" .
                "🚀若想更了解我们的产品，请查阅我们的简介：\n" .
                "https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf\n\n" .
                "期待与您详谈 ！😊",

            'HX3e98ef9c87b7b95ecab108dd5fefa299' =>
                "您好 {{1}}！😊\n\n" .
                "想与您跟进一下，看看您是否有时间浏览我们的宣传册。\n" .
                "如果您有兴趣参加我们的系统演示，请让我知道您方便接听电话的时间，好让我们为您安排。",

            'HX56b6870ea3e16d538bccca337fa7ac84' =>
                "温馨提醒：我们目前仍提供免费生物识别设备！（需符合条款与条件）\n" .
                "这是零成本让您提升HR效率的好机会。😊\n" .
                "如果现在不是合适的时机，或你希望联系其他负责人，请您随时告知。我很乐意协助！",

            'HXf0bfe0b10f2816c62edd73cf2ff017b5' =>
                "这是我最后一次小小的打扰 🙈\n\n" .
                "如果您现在不方便，请告诉我什么时候联系您会更合适，如果需要联系其他负责人，也请您随时告知。\n\n" .
                "如果您之后有兴趣重新了解，我随时愿意与您接洽！😊",

            //Request quotation
            'HX77f305a847b5b6ad0e6f0f424a6179bd' =>
                "Hi {{1}}, as per our phone conversation, please provide your details below so we can provide quotation to you:
                (Minimum headcount is 20 user/staff)

                Department:
                Company Name (As registered in SSM):
                Address:
                Pic Name:
                Email:
                Mobile number:
                HRDF Register:
                Headcount:
                Module interested:",

            'HXf040c1812afbe8b0de069b4301bd31f8' =>
                "您好 {{1}}，根据我们之前的电话沟通，请您协助提供以下资料以便我们报价：

                （最低人数为5位用户/员工）

                部门名称：
                公司名称（需与SSM注册一致）：
                公司地址：
                公司负责人：
                联系邮箱：
                手机号码：
                是否已注册HRDF：
                员工人数：
                感兴趣的产品：",

            //Send demo slot template
            'HX8ffc6fd8b995859aa28fa59ba9712529' =>
                "Hi {{1}}, below is our available online/onsite demo slot:

                {{2}}
                {{3}}
                {{4}}

                Please let me know if you are available to join our demo.

                *The demo will take 1 hour, including a Q&A session

                *Kindly reply if you have received this message.*",

            'HX99cd275a009cf38322ede220d81be784' =>
                "您好，以下是我们的线上/线下演示可选的时段：

                {{1}}
                {{2}}
                {{3}}

                请告知您是否可以参加我们的演示。

                * 整个演示约1小时，其中包含问答环节。

                *如您收到此消息，烦请回复确认。谢谢。*",

            //Send demo confirmation template
            'HX23b2a24ea30108f54de52c467fdb9e54' =>
                "Hi {{1}}, your demo session has been confirmed:

                {{2}} ({{3}}) - {{4}}
                {{5}} Demo
                Salesperson - {{6}}, {{7}}

                The demo will take 1 hour, including a Q&A session. If you have any questions or things you'd like to clarify, feel free to jot them down and bring them up during the demo.

                Our salesperson will be contacting you directly. Please feel free to liaise with them for any further assistance.

                *Please note that the demo will be attended by multiple companies.*",

            'HX412a62868446c87862cfe6980de4bdc7' =>
                "Hi {{1}}, your demo session has been confirmed:

                {{2}} ({{3}}) - {{4}}
                {{5}} Demo
                Salesperson - {{6}}, {{7}}

                The demo will take 1 hour, including a Q&A session. If you have any questions or things you'd like to clarify, feel free to jot them down and bring them up during the demo.

                Our salesperson will be contacting you directly. Please feel free to liaise with them for any further assistance.",

            'HXf85bfd84362e7fe7d9fe8ecb41c6ee17' =>
                "您好！产品演示已为您安排好了，以下是演示的详情：

                {{1}}，{{2}} - {{3}}
                {{4}}演示
                负责人 - {{5}}，{{6}}

                整个演示将进行1小时，我们也预留充足的时间回答您的问题。如果您有任何想了解的内容，欢迎提前准备好问题，届时我们可以重点讨论。

                我们的销售同事会直接与您联系。如果在这之前您有任何疑问，也随时欢迎联系我们。

                期待与您见面交流！

                *请留意，此次系统演示将会有其他公司一同参与，敬请理解。*",

            'HXfa73af7acacd48a0d09ffc3ded7c6e43' =>
                "您好！如果您有兴趣参与演示，请提供以下信息，以便我们为您安排合适的时段：

                （最低人数为5位用户/员工）

                部门名称：
                公司名称（需与SSM注册一致）：
                公司地址：
                公司负责人：
                联系邮箱：
                手机号码：
                是否已注册HRDF：
                员工人数：
                感兴趣的产品：",

            'HX125737e6bb8cbad1c5aae65457d1913b' =>
                "If you're interested, please provide your details below so we can check slot availability for you:
                (Minimum headcount is 20 user/staff)

                Department:
                Company Name (As registered in SSM):
                Address:
                Pic Name:
                Email:
                Mobile number:
                HRDF Register:
                Headcount:
                Module interested:",

            'HXbbf140abfc0185fdc61696545027dea1' =>
                "您好！如果您有兴趣参与演示，请提供以下信息，以便我们为您安排合适的时段：

                （最低人数为20位用户/员工）

                部门名称：
                公司名称（需与SSM注册一致）：
                公司地址：
                公司负责人：
                联系邮箱：
                手机号码：
                是否已注册HRDF：
                员工人数：
                感兴趣的产品：",
        ];

        if (!isset($templates[$contentTemplateSid])) {
            throw new \Exception("Template not found with ID: $contentTemplateSid");
        }

        // Get the template text
        $templateText = $templates[$contentTemplateSid] ?? "Message content unavailable.";

        // Replace placeholders with actual values
        foreach ($variables as $key => $value) {
            $templateText = str_replace("{{" . $key . "}}", $value, $templateText);
        }

        return $templateText;
    }
}
