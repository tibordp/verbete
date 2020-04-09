<?php
require_once './include/lists.inc.php';

$conn_string = sprintf(
    'mysql:host=%s;port=%s;charset=utf8;dbname=%s',
    getenv("MYSQL_SERVER"),
    getenv("MYSQL_PORT"),
    getenv("MYSQL_DATABASE")
);
$db_connection = new PDO(
    $conn_string,
    getenv("MYSQL_USERNAME"),
    getenv("MYSQL_PASSWORD"),
    [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
);

define('COMPOSED_MASK', 7);

class Tense
{
    const PRESENT_SIMPLE = 1;
    const IMPARFAIT = 2;
    const FUTUR_SIMPLE = 3;
    const PASSE_SIMPLE = 4;
    // Basic subjunctive tenses
    const SUBJONCTIF_PRESENT = 5;
    const SUBJONCTIF_IMPARFAIT = 6;
    const CONDITIONEL_PRESENT = 7;

    // Basic composed tenses
    const PASSE_COMPOSE = 8;
    const PLUS_QUE_PARFAIT = 9;
    const FUTUR_ANTERIEUR = 10;
    const PASSE_ANTERIEUR = 11;
    // Composed subjunctive tenses
    const SUBJONCTIF_PASSE = 12;
    const SUBJONCTIF_PQP = 13;
    const CONDITIONEL_PASSE = 14;

    // Not real tenses
    const FUTUR_PROCHE = 15;
    const PASSE_RECENT = 16;
    // Other types
    const IMPERATIF = 17;
    const PARTICIPE_PASSE = 18;
    const PARTICIPE_PRESENT = 19;
}

class VerbConjugator
{
    private $Pronouns = [
        'je',
        'tu',
        'il',
        'elle',
        'nous',
        'vous',
        'ils',
        'elles',
    ];
    private $Reflective = ['me', 'te', 'se', 'nous', 'vous', 'se'];
    private $Imp_Reflective = ['toi', 'nous', 'vous'];
    private $Contractions = [
        'me' => 'm\'',
        'te' => 't\'',
        'que' => 'qu\'',
        'de' => 'd\'',
        'se' => 's\'',
        'je' => 'j\'',
        'ne' => 'n\'',
    ];
    private $AuxilliaryVerbs = [];

    function __construct($include_pronoun)
    {
        $this->AuxilliaryVerbs['avoir'] = $this->GetVerbAttributes('avoir');
        $this->AuxilliaryVerbs['être'] = $this->GetVerbAttributes('être');
    }

    private function GenerateTree(&$array, $tree, $value)
    {
        $ref = &$array;
        foreach ($tree as $val) {
            $ref = &$ref[$val];
        }
        $ref = $value;
    }

    private function WithEtre($verb)
    {
        return in_array($verb, VerbLists::$VerbesEtre);
    }

    private function GetMainVerbSuffix($verb_data, $tense)
    {
        switch ($tense) {
            case Tense::PRESENT_SIMPLE:
            case Tense::PASSE_COMPOSE:
                return $verb_data['indicative']['present'];
            case Tense::IMPARFAIT:
            case Tense::PLUS_QUE_PARFAIT:
                return $verb_data['indicative']['imperfect'];
            case Tense::FUTUR_SIMPLE:
            case Tense::FUTUR_ANTERIEUR:
                return $verb_data['indicative']['future'];
            case Tense::PASSE_SIMPLE:
            case Tense::PASSE_ANTERIEUR:
                return $verb_data['indicative']['past'];
            case Tense::CONDITIONEL_PRESENT:
            case Tense::CONDITIONEL_PASSE:
                return $verb_data['conditional']['present'];
            case Tense::SUBJONCTIF_PRESENT:
            case Tense::SUBJONCTIF_PASSE:
                return $verb_data['subjunctive']['present'];
            case Tense::SUBJONCTIF_IMPARFAIT:
            case Tense::SUBJONCTIF_PQP:
                return $verb_data['subjunctive']['imperfect'];
        }
    }

    private function StartsWithVowel($string, $aspirated_h)
    {
        if ($aspirated_h) {
            $vowels = ['a', 'e', 'i', 'o', 'u', 'é'];
        } else {
            $vowels = ['a', 'e', 'i', 'o', 'u', 'é', 'h'];
        }

        foreach ($vowels as $vowel) {
            // Because of UTF-8
            if (strncmp($vowel, $string, strlen($vowel)) == 0) {
                return true;
            }
        }
        return false;
    }

    private function ContractVowels(&$conjugated_verb, $aspirated_h)
    {
        $length = count($conjugated_verb);

        global $contractions;
        for ($i = 0; $i < $length - 2; $i++) {
            $j = 1;
            while ($conjugated_verb[$i + $j][0] == '') {
                // Just to skip blank fields (e.g. when there is an irregular verb with no prefix)
                $j++;
                if ($i + $j >= $length - 1) {
                    break;
                }
            }
            // var_dump ($conjugated_verb[$i+$j][0]);
            if (
                $this->StartsWithVowel(
                    $conjugated_verb[$i + $j][0],
                    $aspirated_h
                )
            ) {
                switch ($conjugated_verb[$i][1]) {
                    case 'PERSON':
                    case 'ARTICLE':
                    case 'NEGATIVE':
                    case 'REFLECTIVE':
                    case 'SUBJUNCTIVE_PREFIX':
                        if (
                            isset($this->Contractions[$conjugated_verb[$i][0]])
                        ) {
                            $conjugated_verb[$i][0] =
                                $this->Contractions[$conjugated_verb[$i][0]];
                            $conjugated_verb[$i][2] = true;
                        }
                        break;
                }
            }
        }
    }

    public function GetVerbAttributes($verb)
    {
        // First we check if this verb is already among the commonly used verbs in $this->AuxilliaryVerbs
        global $db_connection;

        foreach ($this->AuxilliaryVerbs as $aux_verb => $verb_data) {
            if ($aux_verb == $verb) {
                return $verb_data;
            }
        }

        $query =
            "SELECT verbs.verb,aspirate_h,reflexive,templates.* " .
            "FROM verbs, templates " .
            "WHERE verbs.verb LIKE ? AND BINARY templates.name = verbs.template";
        $stmt = $db_connection->prepare($query);

        if (!$stmt->execute([$verb])) {
            return null;
        }

        $results = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$results) {
            return null;
        }

        $verb_data = [];
        foreach ($results as $field => $value) {
            // We generate associative array from MySQL column names
            $tree = explode('_', $field);
            switch ($field) {
                case 'aspirate_h':
                    $verb_data['aspirate_h'] = (bool) ($value == '1');
                    break;
                case 'reflexive':
                    $verb_data['reflexive'] = (bool) ($value == '1');
                    break;
                default:
                    $this->GenerateTree($verb_data, $tree, $value);
            }
        }

        if (empty($verb_data)) {
            return null;
        }

        foreach (
            $verb_data['participle']['past']
            as $person =>
                $v // To fill every person where there's only one form of past participle.
        ) {
            if ($v == null) {
                $verb_data['participle']['past'][$person] =
                    $verb_data['participle']['past'][0];
            }
        }

        $verb_data =
            [
                // Just to get the most importan attributes to the beginning
                'prefix' => substr(
                    $verb_data['verb'],
                    0,
                    1 +
                        strlen($verb_data['verb']) -
                        (strlen($verb_data['name']) -
                            strpos($verb_data['name'], ':'))
                ),
            ] + $verb_data;

        return $verb_data;
    }

    public function Conjugate($verb_data, $attributes)
    {
        /* Attributes = Array( tense, person, reflective, negative )
         *
         */ $person = $attributes['person'];

        if ($person <= 2) {
            $type_person = $person;
        }
        // Normalization of person (ils => ils/elles, elle => il/elle, ...)
        elseif ($person <= 6) {
            $type_person = $person - 1;
        } elseif ($person == 7) {
            $type_person = 5;
        }

        if (
            $attributes['tense'] == Tense::PRESENT_SIMPLE ||
            $attributes['tense'] == Tense::FUTUR_SIMPLE ||
            $attributes['tense'] == Tense::IMPARFAIT ||
            $attributes['tense'] == Tense::PASSE_SIMPLE ||
            $attributes['tense'] == Tense::SUBJONCTIF_PRESENT ||
            $attributes['tense'] == Tense::SUBJONCTIF_IMPARFAIT ||
            $attributes['tense'] == Tense::CONDITIONEL_PRESENT
        ) {
            $result[] = [$this->Pronouns[$person], 'PERSON'];
            if ($attributes['negative']) {
                $result[] = ['ne', 'NEGATIVE'];
            }
            if ($attributes['reflective']) {
                $result[] = [$this->Reflective[$type_person], 'REFLECTIVE'];
            }
            $result[] = [$verb_data['prefix'], 'VERB_PREFIX', true];
            $indic_forms = $this->GetMainVerbSuffix(
                $verb_data,
                $attributes['tense']
            );

            $result[] = [$indic_forms[$type_person], 'VERB_SUFFIX'];
            if ($attributes['negative']) {
                $result[] = ['pas', 'NEGATIVE_P'];
            }
        } // Indicative forms

        /* ------------------------------------------------------------------------------------------------------ */

        if (
            $attributes['tense'] == Tense::PASSE_COMPOSE ||
            $attributes['tense'] == Tense::FUTUR_ANTERIEUR ||
            $attributes['tense'] == Tense::PLUS_QUE_PARFAIT ||
            $attributes['tense'] == Tense::PASSE_ANTERIEUR ||
            $attributes['tense'] == Tense::SUBJONCTIF_PASSE ||
            $attributes['tense'] == Tense::SUBJONCTIF_PQP ||
            $attributes['tense'] == Tense::PARTICIPE_PASSE ||
            $attributes['tense'] == Tense::CONDITIONEL_PASSE
        ) {
            if ($attributes['tense'] != Tense::PARTICIPE_PASSE) {
                if (
                    $attributes['reflective'] ||
                    $this->WithEtre($verb_data['verb'])
                ) {
                    $auxiliary_verb_data = $this->GetVerbAttributes('être');
                } else {
                    $auxiliary_verb_data = $this->GetVerbAttributes('avoir');
                }
                $result = $this->Conjugate($auxiliary_verb_data, [
                    'tense' => $attributes['tense'] - COMPOSED_MASK,
                    'person' => $person,
                    'reflective' => $attributes['reflective'],
                    'negative' => $attributes['negative'],
                ]);
            }
            $result[] = [$verb_data['prefix'], 'PARTICIPLE_PREFIX', true];

            if (
                $person < 3 ||
                !(
                    $attributes['tense'] == Tense::PARTICIPE_PASSE ||
                    $attributes['reflective'] ||
                    $this->WithEtre($verb_data['verb'])
                )
            ) {
                $part_person = 0;
            }
            // avoir verbs have only one form of participle.
            elseif ($person == 3) {
                $part_person = 2;
            } elseif ($person < 7) {
                $part_person = 1;
            } else {
                $part_person = 3;
            }

            $result[] = [
                $verb_data['participle']['past'][$part_person],
                'PARTICIPLE_SUFFIX',
            ];
        } // Composed tenses (w/ past participle)

        /* ------------------------------------------------------------------------------------------------------ */

        if ($attributes['tense'] == Tense::IMPERATIF) {
            if ($person == 1) {
                $imp_person = 0;
            } elseif ($person == 4) {
                $imp_person = 1;
            } elseif ($person == 5) {
                $imp_person = 2;
            } else {
                return null;
            }

            // $result[] = Array($this->Pronouns[$person], 'PERSON');
            if ($attributes['negative']) {
                $result[] = ['ne', 'NEGATIVE'];
                if ($attributes['reflective']) {
                    $result[] = [$this->Reflective[$type_person], 'REFLECTIVE'];
                }
                $result[] = [$verb_data['prefix'], 'VERB_PREFIX', true];
                $result[] = [
                    $verb_data['imperative'][$imp_person],
                    'VERB_SUFFIX',
                    false,
                ];
                $result[] = ['pas', 'NEGATIVE_P'];
            } else {
                $result[] = [$verb_data['prefix'], 'VERB_PREFIX', true];
                if ($attributes['reflective']) {
                    $result[] = [
                        $verb_data['imperative'][$imp_person],
                        'VERB_SUFFIX',
                        true,
                    ];
                    $result[] = ['-', 'HYPEN', true];
                    $result[] = [
                        $this->Imp_Reflective[$imp_person],
                        'REFLECTIVE',
                    ];
                } else {
                    $result[] = [
                        $verb_data['imperative'][$imp_person],
                        'VERB_SUFFIX',
                    ];
                }
            }
        } // Imperative

        /* ------------------------------------------------------------------------------------------------------ */

        if ($attributes['tense'] == Tense::PARTICIPE_PRESENT) {
            if ($attributes['negative']) {
                $result[] = ['ne', 'NEGATIVE'];
            }
            if ($attributes['reflective']) {
                $result[] = ['se', 'REFLECTIVE'];
            }
            $result[] = [$verb_data['prefix'], 'VERB_PREFIX', true];
            $result[] = [$verb_data['participle']['present'], 'VERB_SUFFIX'];
            if ($attributes['negative']) {
                $result[] = ['pas', 'NEGATIVE_P'];
            }
        } // Present participle

        /* ------------------------------------------------------------------------------------------------------ */

        if (
            $attributes['tense'] == Tense::FUTUR_PROCHE ||
            $attributes['tense'] == Tense::PASSE_RECENT
        ) {
            if ($attributes['tense'] == Tense::FUTUR_PROCHE) {
                $auxiliary_verb_data = $this->GetVerbAttributes('aller');
            } else {
                $auxiliary_verb_data = $this->GetVerbAttributes('venir');
            }

            $result = $this->Conjugate($auxiliary_verb_data, [
                'tense' => Tense::PRESENT_SIMPLE,
                'person' => $person,
                'reflective' => $attributes['reflective'],
                'negative' => $attributes['negative'],
            ]);
            if ($attributes['tense'] == Tense::PASSE_RECENT) {
                $result[] = ['de', 'ARTICLE'];
            }
            $result[] = [$verb_data['prefix'], 'VERB_PREFIX', true];
            $result[] = [$verb_data['infinitive'], 'VERB_SUFFIX'];
        } // Composed tenses (w/ infinitive)

        /* ------------------------------------------------------------------------------------------------------ */

        if (
            $attributes['tense'] == Tense::SUBJONCTIF_PRESENT ||
            $attributes['tense'] == Tense::SUBJONCTIF_IMPARFAIT
        ) {
            array_unshift($result, ['que', 'SUBJUNCTIVE_PREFIX']);
        }
        $this->ContractVowels($result, $verb_data['aspirate_h']);
        //var_dump($result);

        if ($verb_data['indicative']['present'][$type_person] === null) {
            return null;
        }
        foreach (
            $result
            as $v // if a form does not exist
        ) {
            if ($v[0] === null) {
                return null;
            }
        }

        return $result;
    }

    public function GetPlaintext($conjugated_verb)
    {
        $output = '';
        if ($conjugated_verb == null) {
            return '';
        }
        foreach ($conjugated_verb as $element) {
            $output .= $element[0];
            if (!$element[2]) {
                $output .= ' ';
            }
        }
        return $output;
    }
}

?>
