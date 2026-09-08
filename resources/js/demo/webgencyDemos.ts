// Local comparison fixtures only. These are never persisted or published.
export const webgencyDemos = [
    {
        demo_label: 'Dolce Vita', demo_background: '#fffdfb',
        template: { component_key: 'dolce-vita' },
        reference_demo: { date_parts: ['14', 'September', '2025'] },
        content: {
            primary_locale: 'en',
            introduction: ['As we get ready to say “I do,” we feel grateful for the wonderful people in our lives.', 'Your support means the world to us, and we would be honored to have you with us as we begin our life together.'],
            dress_intro: 'We would be very happy if your outfit is in the colours of the wedding theme.',
            ladies: 'Elegant summer dresses in pastel tones. We recommend bringing a hat and sunglasses for comfort.',
            gentlemen: 'Suits or shirts in classic shades. Grey, blue, brown, beige are great choices!',
            palette: ['#faf1db', '#f5d9b1', '#f2cac9', '#afcff1', '#7ebbfa'],
        },
        event: {
            title: 'Alexa & Richard', host_name: 'Alexa', second_host_name: 'Richard', main_date: 'September 14, 2025',
            venue: 'Villa Borghese', address: 'Puerto Vallarta, MX', rsvp_deadline: 'September 30',
            activities: [
                { title: 'Opening of the doors', start_time: '16:30' }, { title: 'Ceremony', start_time: '17:00' },
                { title: 'Cocktail and dancing time', start_time: '18:00' }, { title: 'Dinner', start_time: '20:00' },
                { title: 'Party and Open Bar', start_time: '21:00' }, { title: 'End of the celebration', start_time: '23:00' },
            ],
        },
    },
    {
        demo_label: 'Blossom & Oud', demo_background: '#f9e6d4',
        template: { component_key: 'blossom-oud' },
        // The live specimen displays a zero clock despite its printed future date.
        reference_demo: { display_countdown: ['00', '00', '00', '00'] },
        content: {
            primary_locale: 'fr',
            introduction: ['الآنسة أميرة والسيد يوسف', 'يسعدهما ويشرفهما أن يدعوا حضرتكم الكريمة\nلمشاركتهما فرحة حفل زفافهما', 'وذلك بمشيئة الله تعالى يوم السبت 20 ماي 2027\nعلى الساعة الرابعة مساءً', 'بقاعة'],
            dress_intro: 'We kindly invite you to dress in elegant attire that reflects the style and spirit of our special day.',
            ladies: 'Formal dresses in elegant, polished styles are encouraged.',
            gentlemen: 'Well-tailored suits with classic dress shoes are preferred.',
            palette: ['#60603b', '#360c1a', '#40312c', '#efdfcd'],
        },
        event: {
            title: 'Amira & Yusuf', host_name: 'Amira', second_host_name: 'Yusuf', main_date: 'May 20, 2027',
            date_label: '20 Mai 2027', start_time: 'à partir de 16h', venue: 'Beldi Country Club', address: 'Marrakech, Morocco',
            map_url: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3399.306640246493!2d-8.028903000000001!3d31.570638!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xdafef433680c277%3A0xb6d08dba582fd2dc!2sBELDI%20COUNTRY%20CLUB!5e0!3m2!1sen!2s!4v1780759496019!5m2!1sen!2s',
            activities: [{ title: 'Welcome Reception', start_time: '16:00' }, { title: 'Nikah Ceremony', start_time: '17:00' }, { title: 'Dinner', start_time: '19:00' }, { title: 'Party', start_time: '20:00' }],
        },
    },
    {
        demo_label: 'The Sacred Garden', demo_background: '#f9f0e0',
        template: { component_key: 'sacred-garden' },
        reference_demo: { countdown_at: '2026-09-27T21:00:00Z' },
        content: {
            primary_locale: 'en', blessing: ['Two Souls', 'One destiny', 'A Lifetime written by Allah'],
            introduction: 'Join us for an evening of love, laughter, duas, and unforgettable memories as we begin our forever.',
            dress_intro: 'We kindly ask guests to avoid deep red and maroon attire for the celebration.',
            gift_intro: 'Kindly, no boxed gifts please.',
        },
        event: {
            title: 'Zohan & Rose', host_name: 'Zohan', second_host_name: 'Rose', main_date: 'September 27, 2026',
            date_label: '27.09.26', start_time: '5:00 PM', venue: 'Islamic Center of Melville', address: '118 Old East Neck Road Melville, NY 11747', rsvp_deadline: 'August 09',
            map_url: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3021.060117798216!2d-73.40328222397078!3d40.78269247138302!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89e82bdb8c8a016b%3A0x50d592c0865c6b6e!2sIslamic%20Center%20of%20Melville!5e0!3m2!1sen!2smu!4v1782314483584!5m2!1sen!2smu',
            activities: [{ title: 'Guest Arrival', start_time: '5 PM' }, { title: 'Nikkah Ceremony', start_time: '6 PM' }, { title: 'Mocktail Hour', start_time: '7 PM' }, { title: 'Dinner', start_time: '8 PM' }, { title: 'Dance', start_time: '9 PM' }],
        },
    },
];
