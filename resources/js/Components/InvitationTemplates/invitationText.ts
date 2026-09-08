// Trusted presentation labels; customer text is always rendered as escaped text.
const arabic: Record<string, string> = {
    'Dear friends and family,': 'أهلنا وأصدقاؤنا الأعزاء،', 'Dear': 'أعزّاءنا',
    'Open invitation': 'افتح الدعوة', 'Tap to open': 'اضغط للفتح', 'Click to open': 'اضغط للفتح', 'Scroll down': 'انتقل للأسفل',
    'The Date': 'التاريخ', 'Wedding Day': 'يوم الزفاف', 'The Celebration Begins In': 'يبدأ الاحتفال بعد',
    'Schedule of Events': 'برنامج الاحتفال', 'Wedding Venue': 'مكان الاحتفال', 'Location': 'الموقع', 'Directions': 'الاتجاهات',
    'Dress Code': 'اللباس', 'Our Story': 'قصتنا', 'Gift Registry': 'قائمة الهدايا',
    'Confirm Your Attendance': 'تأكيد الحضور', 'Hope to see you there!': 'نتطلع إلى حضوركم!',
    'Days': 'أيام', 'Hours': 'ساعات', 'Minutes': 'دقائق', 'Seconds': 'ثوانٍ',
    'RSVP': 'تأكيد الحضور', 'Address:': 'العنوان:', 'Directions on Google Maps': 'الاتجاهات على خرائط Google',
    'To help us prepare for a joyful celebration, kindly confirm your attendance.': 'لمساعدتنا في التحضير للاحتفال، يُرجى تأكيد حضوركم.',
    'Scratch to reveal the date': 'امسح لإظهار التاريخ', "You're invited!": 'أنتم مدعوون!', 'are getting married!': 'يحتفلان بزفافهما!',
    'DAY': 'اليوم', 'MONTH': 'الشهر', 'YEAR': 'السنة',
    'Meeting Point': 'نقطة اللقاء', 'Celebration': 'الاحتفال', 'Guest information': 'معلومات الضيوف', 'View on map ↗': 'عرض على الخريطة ↗',
    'Reveal': 'اكتشفوا التاريخ', 'Scratch to discover the date': 'امسحوا لإظهار التاريخ', "We're getting married!": 'نحتفل بزفافنا!',
    'Countdown': 'العدّ التنازلي', 'Until the big day': 'حتى يوم الاحتفال', 'How to get there': 'كيفية الوصول',
    'Wedding Locations': 'مواقع الاحتفال', 'Please use the location links below to find your way to each part of the day.': 'استخدموا الروابط أدناه للوصول إلى مواقع الاحتفال.',
    'Wedding registry': 'قائمة هدايا الزفاف', 'Gifts': 'الهدايا', 'With all our love': 'مع كل حبنا', 'Gift details': 'تفاصيل الهدايا',
    'View registry': 'عرض قائمة الهدايا', 'View registry ↗': 'عرض قائمة الهدايا ↗', 'Attendance confirmation': 'تأكيد الحضور',
    'Confirm your attendance': 'أكّدوا حضوركم', 'Will you be joining the celebration?': 'هل ستشاركوننا الاحتفال؟',
    'Directions ↗': 'الاتجاهات ↗', 'Scroll': 'انتقل للأسفل', 'Wedding day': 'يوم الزفاف',
    "We can't wait to celebrate with you!": 'نتطلع للاحتفال معكم!',
};
export function invitationText(invitation: any, text: string): string {
    return invitation.content?.primary_locale === 'ar' ? arabic[text] || text : text;
}
export function familyGreeting(invitation: any): string {
    return invitation.party_name ? `${invitationText(invitation, 'Dear')} ${invitation.party_name}` : invitationText(invitation, 'Dear friends and family,');
}
