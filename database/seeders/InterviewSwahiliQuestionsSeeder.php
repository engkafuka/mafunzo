<?php

namespace Database\Seeders;

use App\Models\InterviewQuestion;
use App\Models\InterviewQuestionSet;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the official WRRB operator interview questions (Kiswahili).
 * Max total = 100 (5 × 20). Language is kept in Swahili as provided.
 */
class InterviewSwahiliQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'interview.admin@wrrb.test')->first()
            ?? User::where('role', 'super_admin')->first()
            ?? User::whereHas('interviewRoles', fn ($q) => $q->where('role', 'admin'))->first();

        $set = InterviewQuestionSet::query()
            ->where('title', 'Mahojiano ya Kampuni ya mwendesha ghala — Seti Rasmi')
            ->orWhere('title', 'Mahojiano ya Opereta wa Ghala — Seti Rasmi')
            ->first();

        if ($set) {
            $set->update([
                'title' => 'Mahojiano ya Kampuni ya mwendesha ghala — Seti Rasmi',
                'version' => '1.0',
                'description' => 'Maswali rasmi ya mahojiano ya kampuni ya mwendesha ghala (Kiswahili). Jumla ya alama: 300 (maswali 15 × 20).',
                'is_active' => true,
                'created_by' => $admin?->id ?? $set->created_by,
            ]);
        } else {
            $set = InterviewQuestionSet::create([
                'title' => 'Mahojiano ya Kampuni ya mwendesha ghala — Seti Rasmi',
                'version' => '1.0',
                'description' => 'Maswali rasmi ya mahojiano ya kampuni ya mwendesha ghala (Kiswahili). Jumla ya alama: 300 (maswali 15 × 20).',
                'is_active' => true,
                'created_by' => $admin?->id,
            ]);
        }

        $questions = [
            [
                'sort_order' => 1,
                'category' => 'integrity',
                'question_text' => 'Eleza nani anawajibika kikamilifu pale mzigo unapopotea, kuharibika au kuchafuka, na fafanua mfumo wa utawala unaotekeleza uwajibikaji huo ikiwa ni pamoja na mikataba, hatua za kinidhamu na bima ya ghala.',
                'rubric' => 'Swali la 1 — Alama 20. Angalia uwazi wa uwajibikaji, mikataba, nidhamu na bima.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 2,
                'category' => 'records',
                'question_text' => 'Eleza mfumo wenu wa ulinganishaji wa mzigo, ukionyesha jinsi mzigo, stakabadhi na salio vinavyohakikiwa, pamoja na taratibu, muda na wahusika wa stock-taking.',
                'rubric' => 'Swali la 2 — Alama 20. Angalia ulinganishaji wa mzigo, stakabadhi, salio na stock-taking.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 3,
                'category' => 'operations',
                'question_text' => 'Eleza mchakato mzima wa kuandaa, kuhakiki na kuidhinisha sales catalogue, pamoja na udhibiti wa kuzuia kutangaza mzigo usiokuwepo au uliokwisha kutumika kama dhamana, na hatua zinazochukuliwa panapotokea tofauti.',
                'rubric' => 'Swali la 3 — Alama 20. Angalia sales catalogue, udhibiti wa dhamana na hatua za tofauti.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 4,
                'category' => 'quality',
                'question_text' => 'Eleza jinsi unyafu wa mzigo unavyohesabiwa, kuidhinishwa na kudhibitiwa, pamoja na viwango vinavyokubalika kisheria, mawasiliano kwa mteja na uwajibikaji pale unyafu unapozidi kiwango.',
                'rubric' => 'Swali la 4 — Alama 20. Angalia unyafu, viwango vya kisheria, mawasiliano na uwajibikaji.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 5,
                'category' => 'quality',
                'question_text' => 'Eleza mchakato wa ukaguzi wa ubora wa mzigo katika hatua zote (kabla ya kuhifadhi, wakati wa kuhifadhi na kabla ya kutolewa), pamoja na wahusika, sifa zao, mstari wa taarifa na nyaraka zinazotumika.',
                'rubric' => 'Swali la 5 — Alama 20. Angalia ukaguzi wa ubora katika hatua zote, wahusika na nyaraka.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 6,
                'category' => 'quality',
                'question_text' => 'Jinsi ya kupata ubora wa ufuta.',
                'rubric' => 'Swali la 6 — Alama 20. Angalia uelewa wa ubora wa ufuta.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 7,
                'category' => 'quality',
                'question_text' => 'Unatumia mbinu gani kuhakikisha ufuta unaoingia ghalani una ubora.',
                'rubric' => 'Swali la 7 — Alama 20. Angalia mbinu za kuhakikisha ubora wa ufuta unaoingia ghalani.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 8,
                'category' => 'integrity',
                'question_text' => 'Eleza taratibu zinazofuatwa pale mzigo unapochafuka au kuharibika ghalani, ikiwa ni pamoja na uwajibikaji wa kisheria na kifedha, ushughulikiaji wa malalamiko na fidia.',
                'rubric' => 'Swali la 8 — Alama 20. Angalia taratibu, uwajibikaji wa kisheria/kifedha, malalamiko na fidia.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 9,
                'category' => 'governance',
                'question_text' => 'Eleza muundo wa watumishi wanaosimamia ghala.',
                'rubric' => 'Swali la 9 — Alama 20. Angalia muundo wa watumishi wa usimamizi wa ghala.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 10,
                'category' => 'governance',
                'question_text' => 'Eleza programu ya mafunzo kwa watumishi.',
                'rubric' => 'Swali la 10 — Alama 20. Angalia programu ya mafunzo kwa watumishi.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 11,
                'category' => 'operations',
                'question_text' => 'Eleza teknolojia zinazotumika kudhibiti na kufuatilia shughuli za watumishi ghalani kama CCTV, access control na mifumo ya TEHAMA, pamoja na uhifadhi wa kumbukumbu, haki za ufikiaji na ufanisi wake.',
                'rubric' => 'Swali la 11 — Alama 20. Angalia CCTV, access control, TEHAMA, kumbukumbu na haki za ufikiaji.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 12,
                'category' => 'operations',
                'question_text' => 'Eleza mfumo wenu wa kidijitali wa ghala, ikiwa ni pamoja na udhibiti wa watumiaji wanaoweza kuingiza, kubadilisha au kufuta taarifa, na hatua za usalama wa mfumo.',
                'rubric' => 'Swali la 12 — Alama 20. Angalia mfumo wa kidijitali, udhibiti wa watumiaji na usalama.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 13,
                'category' => 'compliance',
                'question_text' => 'Eleza jinsi malalamiko ya wateja yanavyopokelewa, kurekodiwa, kushughulikiwa na kufungwa, pamoja na muda wa kushughulikia, wahusika na utunzaji wa kumbukumbu.',
                'rubric' => 'Swali la 13 — Alama 20. Angalia mzunguko wa malalamiko, muda, wahusika na kumbukumbu.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 14,
                'category' => 'compliance',
                'question_text' => 'Eleza jinsi wateja wanavyoarifiwa kuhusu mabadiliko yanayoathiri mzigo wao, pamoja na muda wa taarifa na jinsi ya kuhakikisha taarifa zinaendana na kumbukumbu za ghala na sales catalogue.',
                'rubric' => 'Swali la 14 — Alama 20. Angalia taarifa kwa wateja, muda na ulinganifu na kumbukumbu/sales catalogue.',
                'max_mark' => 20,
            ],
            [
                'sort_order' => 15,
                'category' => 'compliance',
                'question_text' => 'Eleza jinsi uwazi, uwajibikaji na mifumo ya udhibiti inavyosaidia kuhakikisha uzingatiaji wa kanuni za ghala na kulinda maslahi ya wateja.',
                'rubric' => 'Swali la 15 — Alama 20. Angalia uwazi, uwajibikaji, udhibiti na ulinzi wa maslahi ya wateja.',
                'max_mark' => 20,
            ],
        ];

        // Replace existing questions on this set so re-seeding stays consistent
        $set->questions()->delete();

        foreach ($questions as $question) {
            InterviewQuestion::create([
                'question_set_id' => $set->id,
                'sort_order' => $question['sort_order'],
                'category' => $question['category'],
                'question_text' => $question['question_text'],
                'rubric' => $question['rubric'],
                'max_mark' => $question['max_mark'],
                'weight' => 1,
                'is_active' => true,
            ]);
        }

        $this->command?->info('Question set: '.$set->title);
        $this->command?->info('Questions seeded: '.$set->questions()->count().' (max '.$set->maxPossibleScore().')');
    }
}
