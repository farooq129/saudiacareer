<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Blog sections and a handful of articles to open the section with.
 *
 * Idempotent on both tables, so re-running it after a schema change refreshes
 * the copy without duplicating a URL. Nothing here sets views_count — that is
 * real traffic and the seeder has no business inventing it.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'key' => 'career-advice',
                'name_ar' => 'نصائح مهنية',
                'name_en' => 'Career advice',
                'blurb_ar' => 'كيف تبحث عن عمل في السعودية، وكيف تقنع صاحب العمل.',
                'blurb_en' => 'How to look for work in Saudi Arabia, and how to convince an employer.',
                'sort_order' => 10,
            ],
            [
                'key' => 'hiring',
                'name_ar' => 'التوظيف',
                'name_en' => 'Hiring',
                'blurb_ar' => 'إرشادات لأصحاب العمل: كتابة الإعلان، وفرز المتقدمين.',
                'blurb_en' => 'For employers: writing the ad, and sorting the applicants.',
                'sort_order' => 20,
            ],
            [
                'key' => 'labour-law',
                'name_ar' => 'نظام العمل',
                'name_en' => 'Labour law',
                'blurb_ar' => 'نقل الكفالة، والعقود، وما يقوله النظام السعودي للطرفين.',
                'blurb_en' => 'Iqama transfer, contracts, and what Saudi law says for both sides.',
                'sort_order' => 30,
            ],
        ];

        foreach ($sections as $section) {
            BlogCategory::updateOrCreate(['key' => $section['key']], $section + ['is_active' => true]);
        }

        $author = User::query()->where('role', User::ROLE_ADMIN)->first();

        $posts = [
            [
                'slug' => 'writing-a-cv-that-gets-read-in-saudi-arabia',
                'section' => 'career-advice',
                'title_ar' => 'كيف تكتب سيرة ذاتية تُقرأ فعلًا',
                'title_en' => 'Writing a CV that actually gets read',
                'excerpt_ar' => 'صاحب العمل يقرأ سيرتك في أقل من دقيقة. هذه هي الأسطر التي يقرؤها أولًا.',
                'excerpt_en' => 'An employer reads your CV in under a minute. These are the lines they read first.',
                'is_featured' => true,
                'body_ar' => [
                    'أغلب من ينشر وظيفة على لوحة إعلانات يتلقى عشرات الطلبات في اليوم الأول. هذا يعني أن سيرتك الذاتية لن تُقرأ كاملة في المرة الأولى، بل ستُمسح بالعين بحثًا عن ثلاثة أشياء: المسمى الوظيفي، والمدينة، وهل يمكن توظيفك قانونيًا الآن.',
                    'ضع هذه الثلاثة في أعلى الصفحة. لا تخبئ المدينة في آخر سطر ولا تترك وضع الإقامة للتخمين، فصاحب العمل الذي لا يجد الإجابة ينتقل إلى الطلب التالي بدل أن يسأل.',
                    'بعد ذلك اكتب خبرتك بالأرقام لا بالصفات. «أدرت فرعًا فيه ثمانية موظفين» تقول أكثر بكثير من «قيادي وصاحب خبرة». الأرقام يمكن التحقق منها، والصفات يكتبها الجميع.',
                    'أخيرًا، اجعل رقم التواصل صحيحًا وظاهرًا. يبدو هذا بديهيًا، لكن جزءًا حقيقيًا من الطلبات يصل بأرقام ناقصة، وصاحب العمل لن يبحث عنك.',
                ],
                'body_en' => [
                    'Most people who post a vacancy on a job board get dozens of applications on the first day. That means your CV will not be read end to end the first time. It will be scanned for three things: the job title, the city, and whether you can legally be hired right now.',
                    'Put all three at the top. Do not bury the city in the last line, and do not leave your residency status to guesswork — an employer who cannot find the answer moves to the next application rather than asking.',
                    'Then describe your experience in numbers rather than adjectives. "Ran a branch with eight staff" says far more than "experienced leader". Numbers can be checked; adjectives are written by everybody.',
                    'Finally, make sure your contact number is correct and visible. This sounds obvious, but a real share of applications arrive with an incomplete number, and no employer is going to go looking for you.',
                ],
            ],
            [
                'slug' => 'what-iqama-transfer-actually-means',
                'section' => 'labour-law',
                'title_ar' => 'ما الذي يعنيه «نقل الكفالة» فعلًا',
                'title_en' => 'What iqama transfer actually means',
                'excerpt_ar' => 'أكثر شرط يتكرر في إعلانات الوظائف، وأكثر ما يُساء فهمه.',
                'excerpt_en' => 'The condition that appears in more job ads than any other, and the one most often misunderstood.',
                'body_ar' => [
                    'نقل الكفالة هو انتقال رعاية العامل من صاحب عمل إلى آخر. حين يكتب الإعلان «نقل كفالة متاح» فهو يقول إن صاحب العمل مستعد لإتمام هذا الإجراء، لا أنه سيتم تلقائيًا.',
                    'الشرط الأساسي أن تكون إقامتك سارية وأن يكون وضع منشأة صاحب العمل الحالي والجديد يسمح بذلك. هذه أمور تُفحص قبل التقديم لا بعده، ويوفر فحصها المبكر أسابيع على الطرفين.',
                    'إن كان إعلان الوظيفة لا يذكر نقل الكفالة إطلاقًا، فاسأل قبل أن تتقدم. السؤال في الرسالة الأولى أفضل من اكتشافه بعد مقابلتين.',
                ],
                'body_en' => [
                    'Iqama transfer is the move of a worker\'s sponsorship from one employer to another. When an ad says "transfer available", it is saying the employer is willing to complete that process — not that it happens automatically.',
                    'The basic requirement is that your iqama is valid and that both the current and the new employer\'s establishment status allows it. These are things to check before you apply rather than after, and checking early saves both sides weeks.',
                    'If a job ad does not mention transfer at all, ask before applying. Asking in the first message is better than finding out after two interviews.',
                ],
            ],
            [
                'slug' => 'writing-a-job-ad-that-attracts-the-right-people',
                'section' => 'hiring',
                'title_ar' => 'كيف تكتب إعلان وظيفة يجذب المناسبين',
                'title_en' => 'Writing a job ad that attracts the right people',
                'excerpt_ar' => 'الإعلان الغامض يجلب مئة طلب غير مناسب. الإعلان الواضح يجلب عشرة مناسبين.',
                'excerpt_en' => 'A vague ad brings a hundred wrong applications. A clear one brings ten right ones.',
                'body_ar' => [
                    'الإعلان الذي لا يذكر الراتب ولا المدينة ولا ساعات العمل يبدو مرنًا لصاحبه، لكنه في الواقع يدفع كل من يقرؤه إلى التخمين. والنتيجة عدد كبير من الطلبات وقليل منها مناسب.',
                    'اذكر نطاق الراتب. هذا أكثر سطر يُقرأ في أي إعلان، وحذفه لا يخفيه بل يؤجل السؤال إلى المقابلة الأولى حيث يكلّف وقت الطرفين.',
                    'اكتب ثلاثة شروط أساسية لا عشرة. قائمة الشروط الطويلة تُقصي المرشح الجيد الذي ينقصه شرط واحد، بينما لا تردع من يتقدم لكل إعلان.',
                ],
                'body_en' => [
                    'An ad that names no salary, no city and no hours feels flexible to the person writing it, but it forces everyone reading it to guess. The result is a large number of applications and very few of them suitable.',
                    'Name a salary range. It is the most-read line in any ad, and leaving it out does not hide it — it just defers the question to the first interview, where it costs both sides time.',
                    'List three real requirements, not ten. A long list screens out the good candidate who is missing one item, while doing nothing to deter the person who applies to every ad.',
                ],
            ],
        ];

        foreach ($posts as $row) {
            $section = BlogCategory::where('key', $row['section'])->first();

            BlogPost::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'blog_category_id' => $section?->id,
                    'user_id' => $author?->id,
                    'title_ar' => $row['title_ar'],
                    'title_en' => $row['title_en'],
                    'excerpt_ar' => $row['excerpt_ar'],
                    'excerpt_en' => $row['excerpt_en'],
                    'body_ar' => $row['body_ar'],
                    'body_en' => $row['body_en'],
                    'is_featured' => $row['is_featured'] ?? false,
                    'is_indexable' => true,
                    'status' => BlogPost::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(count($posts) - array_search($row, $posts, true)),
                ],
            );
        }
    }
}
