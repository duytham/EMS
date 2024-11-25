<?php
class EmailContent {
    public static function getCheckInEmailContent($employeeName) {
        return [
            'subject' => "Check-in Reminder",
            'body' => "Dear {$employeeName},<br><br>
                        This is a friendly reminder to check in at the office. Please ensure your attendance is recorded on time.<br><br>
                        Best regards,<br>
                        EMS Team"
        ];
    }

    public static function getCheckOutEmailContent($employeeName) {
        return [
            'subject' => "Check-out Reminder",
            'body' => "Dear {$employeeName},<br><br>
                        This is a reminder to check out at the end of your working day. Thank you for your hard work!<br><br>
                        Best regards,<br>
                        EMS Team"
        ];
    }
}
?>
