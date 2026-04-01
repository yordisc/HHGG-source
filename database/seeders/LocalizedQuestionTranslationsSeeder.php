<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionTranslation;
use Illuminate\Database\Seeder;

class LocalizedQuestionTranslationsSeeder extends Seeder
{
    /**
     * Seed localized question translations for non-English locales.
     */
    public function run(): void
    {
        $locales = ['es', 'pt', 'fr', 'zh', 'hi', 'ar'];

        Question::query()->chunkById(200, function ($questions) use ($locales): void {
            $rows = [];
            $now = now();

            foreach ($questions as $question) {
                foreach ($locales as $locale) {
                    [$prompt, $option1, $option2, $option3, $option4] = $this->translateQuestion($question->cert_type, $locale, $question->prompt);

                    $rows[] = [
                        'question_id' => $question->id,
                        'language' => $locale,
                        'prompt' => $prompt,
                        'option_1' => $option1,
                        'option_2' => $option2,
                        'option_3' => $option3,
                        'option_4' => $option4,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            QuestionTranslation::query()->upsert(
                $rows,
                ['question_id', 'language'],
                ['prompt', 'option_1', 'option_2', 'option_3', 'option_4', 'updated_at']
            );
        });
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function translateQuestion(string $certType, string $locale, string $basePrompt): array
    {
        $suffix = $this->extractNumericSuffix($basePrompt);

        if ($certType === 'hetero') {
            return $this->heteroTranslations($locale, $suffix);
        }

        if ($certType === 'good_girl') {
            return $this->goodGirlTranslations($locale, $suffix);
        }

        return [
            $basePrompt,
            'Option 1',
            'Option 2',
            'Option 3',
            'Option 4',
        ];
    }

    private function extractNumericSuffix(string $prompt): string
    {
        if (preg_match('/#(\d+)\s*$/', $prompt, $matches) === 1) {
            return ' #'.$matches[1];
        }

        return '';
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function heteroTranslations(string $locale, string $suffix): array
    {
        return match ($locale) {
            'es' => [
                'En un evento social, que te describe mejor?'.$suffix,
                'Saludo rapido a muchas personas',
                'Prefiero una conversacion profunda',
                'Primero observo y luego participo',
                'Me quedo cerca de amistades cercanas',
            ],
            'pt' => [
                'Em um evento social, o que melhor descreve voce?'.$suffix,
                'Cumprimento muitas pessoas rapidamente',
                'Prefiro uma conversa mais profunda',
                'Primeiro observo e depois participo',
                'Fico perto de amigos proximos',
            ],
            'fr' => [
                'Lors d un evenement social, que vous decrit le mieux?'.$suffix,
                'Je salue rapidement beaucoup de personnes',
                'Je prefere une conversation plus profonde',
                'J observe d abord puis je participe',
                'Je reste pres de mes amis proches',
            ],
            'zh' => [
                '在社交活动中，哪一项最能描述你？'.$suffix,
                '我会快速和很多人打招呼',
                '我更喜欢一次深入的对话',
                '我先观察再加入',
                '我会待在熟悉的朋友身边',
            ],
            'hi' => [
                'Social event me aapko sabse achchha kya describe karta hai?'.$suffix,
                'Main jaldi se bahut logon se milta hoon',
                'Main ek gehri baat-cheet pasand karta hoon',
                'Main pehle observe karta hoon phir join karta hoon',
                'Main kareebi doston ke paas rehta hoon',
            ],
            'ar' => [
                'في مناسبة اجتماعية، ما الوصف الأقرب لك؟'.$suffix,
                'أحيي عددا كبيرا من الناس بسرعة',
                'أفضل محادثة واحدة عميقة',
                'أراقب أولا ثم أشارك',
                'أبقى قريبا من أصدقائي المقربين',
            ],
            default => [
                'At a social event, what describes you best?'.$suffix,
                'I greet many people quickly',
                'I prefer one deep conversation',
                'I observe first and then join',
                'I stay near close friends',
            ],
        };
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function goodGirlTranslations(string $locale, string $suffix): array
    {
        return match ($locale) {
            'es' => [
                'Como sueles manejar tus planes semanales?'.$suffix,
                'Planifico tareas con anticipacion',
                'Improviso segun lo que pase',
                'Defino prioridades pero mantengo flexibilidad',
                'Pido a otros organizar conmigo',
            ],
            'pt' => [
                'Como voce costuma organizar seus planos da semana?'.$suffix,
                'Planejo tarefas com antecedencia',
                'Improviso conforme as coisas acontecem',
                'Defino prioridades mas mantenho flexibilidade',
                'Peco para outras pessoas organizarem comigo',
            ],
            'fr' => [
                'Comment gerez-vous habituellement vos plans hebdomadaires?'.$suffix,
                'Je planifie mes taches a l avance',
                'J improvise selon les evenements',
                'Je fixe des priorites mais je reste flexible',
                'Je demande aux autres de s organiser avec moi',
            ],
            'zh' => [
                '你通常如何安排每周计划？'.$suffix,
                '我会提前规划任务',
                '我会根据情况即兴处理',
                '我会设定优先级但保持灵活',
                '我会请别人和我一起安排',
            ],
            'hi' => [
                'Aap apne weekly plans ko aam taur par kaise handle karte hain?'.$suffix,
                'Main pehle se tasks plan karta hoon',
                'Main situation ke hisab se improvise karta hoon',
                'Main priorities set karta hoon par flexible rehta hoon',
                'Main dusron se milkar organize karne ko kehta hoon',
            ],
            'ar' => [
                'كيف تتعامل عادة مع خططك الأسبوعية؟'.$suffix,
                'أخطط للمهام مسبقا',
                'أتصرف تلقائيا حسب ما يحدث',
                'أحدد الأولويات لكن أبقى مرنا',
                'أطلب من الآخرين التنظيم معي',
            ],
            default => [
                'How do you usually handle your weekly plans?'.$suffix,
                'I plan tasks in advance',
                'I improvise as things happen',
                'I set priorities but stay flexible',
                'I ask others to organize with me',
            ],
        };
    }
}
