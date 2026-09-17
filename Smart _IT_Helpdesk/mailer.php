<?php
// mailer.php - Procedural Email Helper for Smart IT Helpdesk

/**
 * ฟังก์ชันหลักสำหรับส่งอีเมลแจ้งเตือน
 * ใช้ Brevo API (ส่งฟรีวันละ 300 ฉบับ ไม่ติดพอร์ต SMTP บน Localhost)
 */
function send_email_notification($to_email, $to_name, $subject, $html_content)
{
    // 1. นำ API Key จาก Brevo (brevo.com) มาใส่ตรงนี้
    $api_key = 'xkeysib-YOUR_BREVO_API_KEY_HERE';

    // กรณีต้องการทดสอบแบบ Mock สำหรับเดโมส่งอาจารย์ (หากยังไม่มี API Key)
    if (str_contains($api_key, 'YOUR_BREVO_API_KEY')) {
        return mock_save_email_log($to_email, $subject, $html_content);
    }

    $payload = [
        'sender' => [
            'name' => 'Smart IT Helpdesk System',
            'email' => 'helpdesk@yourdomain.com'
        ],
        'to' => [
            ['email' => $to_email, 'name' => $to_name]
        ],
        'subject' => $subject,
        'htmlContent' => $html_content
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $api_key,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_code >= 200 && $http_code < 300;
}

/**
 * เทมเพลตอีเมลแจ้งช่างเมื่อมีเคสแจ้งซ่อมใหม่
 */
function build_new_ticket_email($ticket_id, $title, $category, $location, $priority, $desc)
{
    return "
    <div style='font-family: Arial, sans-serif; background-color: #f8fafc; padding: 24px;'>
      <div style='max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden;'>
        <div style='background-color: #4338ca; color: #ffffff; padding: 16px 20px;'>
          <h2 style='margin: 0; font-size: 18px;'>[เคสแจ้งซ่อมใหม่ #{$ticket_id}] {$title}</h2>
        </div>
        <div style='padding: 20px; color: #334155; font-size: 14px; line-height: 1.6;'>
          <p><strong>หมวดหมู่:</strong> {$category}</p>
          <p><strong>สถานที่:</strong> {$location}</p>
          <p><strong>ระดับความเร่งด่วน:</strong> <span style='color: #dc2626; font-weight: bold;'>{$priority}</span></p>
          <p><strong>รายละเอียดปัญหา:</strong></p>
          <div style='background: #f1f5f9; padding: 12px; border-radius: 6px;'>{$desc}</div>
        </div>
        <div style='background: #f8fafc; padding: 12px 20px; text-align: center; color: #64748b; font-size: 12px; border-top: 1px solid #e2e8f0;'>
          ระบบ Smart IT Helpdesk อัตโนมัติ
        </div>
      </div>
    </div>";
}

/**
 * เทมเพลตอีเมลแจ้งผู้ใช้เมื่อช่างปิดงาน
 */
function build_closed_ticket_email($ticket_id, $title)
{
    return "
    <div style='font-family: Arial, sans-serif; background-color: #f8fafc; padding: 24px;'>
      <div style='max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden;'>
        <div style='background-color: #059669; color: #ffffff; padding: 16px 20px;'>
          <h2 style='margin: 0; font-size: 18px;'>งานแจ้งซ่อม #{$ticket_id} ได้รับการแก้ไขแล้ว</h2>
        </div>
        <div style='padding: 20px; color: #334155; font-size: 14px; line-height: 1.6;'>
          <p>เจ้าหน้าที่ไอทีได้ดำเนินการตรวจสอบและปิดงานแจ้งซ่อมหัวข้อ:</p>
          <p style='font-size: 16px; font-weight: bold; color: #1e293b;'>{$title}</p>
          <p>หากอุปกรณ์ยังพบปัญหาเดิม สามารถติดต่อเจ้าหน้าที่เพื่อดำเนินการตรวจสอบซ้ำได้</p>
        </div>
        <div style='background: #f8fafc; padding: 12px 20px; text-align: center; color: #64748b; font-size: 12px; border-top: 1px solid #e2e8f0;'>
          ขอบคุณที่ใช้บริการ Smart IT Helpdesk
        </div>
      </div>
    </div>";
}

/**
 * ฟังก์ชัน Mock: บันทึกประวัติอีเมลลงไฟล์ข้อความ (สำหรับเปิดให้อาจารย์ดูตอนเดโม)
 */
function mock_save_email_log($to_email, $subject, $html_content)
{
    $log_entry = "========================================\n"
        . "เวลา: " . date('Y-m-d H:i:s') . "\n"
        . "ส่งถึง: " . $to_email . "\n"
        . "หัวข้อ: " . $subject . "\n"
        . "เนื้อหา:\n" . strip_tags($html_content) . "\n"
        . "========================================\n\n";

    file_put_contents(__DIR__ . '/mail_log.txt', $log_entry, FILE_APPEND);
    return true;
}