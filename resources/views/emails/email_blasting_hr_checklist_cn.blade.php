<!DOCTYPE html>
<html>
<head>
    <title>还在寻找简化 HR 流程的方法吗？</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p><strong>{{ $lead['lastName'] }}</strong> 您好，</p>

    <p>我是 TimeTec 的 <strong>{{ $leadOwnerName }}</strong>。不久前，您下载了我们的人资管理清单。</p>

    <p>我想向您跟进一下，看看您是否还在寻找简化 HR 流程的方法。TimeTec 的 HR 云端系统能自动化处理<strong>考勤、薪资、休假及报销</strong>，让您告别繁琐的手动文书工作。</p>

    <p>目前我们正推出限时促销，只要订阅我们的考勤模块（TimeTec Attendance），即可<strong>免费获得生物识别设备（Biometric Device）</strong>（需符合条款与条件）。</p>

    <p>如果您有兴趣深入了解，我们可以为您安排一个简短的系统演示（Demo）。</p>

    <p>欢迎查看我们的产品简介
        <a href="https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf" target="_blank">点击这里</a>。
    </p>

    <p>祝商祺！</p>
    <p>{{ $leadOwnerName }}<br>
        {{ $lead['position'] }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Office: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
