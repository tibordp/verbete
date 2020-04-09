<?php
require_once './include/backend.inc.php';
require_once './include/lists.inc.php';

$request_verb = trim($_REQUEST["verbe"]);

if (empty($request_verb)) {
    header('Location: //www.ojdip.net/verbete/');
    die();
}

class States
{
    const NON_EXISTANT = 0;
    const AD_HOC = 1;
    const EXISTANT = 2;
}

$verb_conjugator = new VerbConjugator(true);

if ($data = $verb_conjugator->GetVerbAttributes($request_verb)) {
    // Le verbe existe
    $verb = $data["prefix"] . $data["infinitive"]; // Afin que `etre` devienne `être`.
    $state = States::EXISTANT;
} else {
    foreach (VerbLists::$AdHoc as $ad_hoc_model) {
        if (preg_match("/^(.*){$ad_hoc_model['suffix']}$/", $request_verb)) {
            $data = $verb_conjugator->GetVerbAttributes(
                $ad_hoc_model['template']
            );
            $model = $ad_hoc_model['template'];
            $radix = substr(
                $request_verb,
                0,
                1 +
                    strlen($request_verb) -
                    (strlen($data['name']) - strpos($data['name'], ':'))
            );
            break;
        }
    }

    if (!$data) {
        $state = States::NON_EXISTANT;
    } else {
        $state = States::AD_HOC;
        $data["prefix"] = $radix;
    }

    $verb = $request_verb;
}

function FormatVerb($verb_description)
{
    $text = '<tr><td>';

    if ($verb_description == null) {
        return '<tr><td colspan="2  ">&mdash;</td></tr>';
    }

    $switch = 0;

    foreach ($verb_description as $value) {
        switch ($value[1]) {
            case 'PERSON':
            case 'ARTICLE':
            case 'NEGATIVE':
            case 'NEGATIVE_P':
            case 'REFLECTIVE':
            case 'SUBJUNCTIVE_PREFIX':
                break;
            case 'VERB_PREFIX':
            case 'PARTICIPLE_PREFIX':
            case 'VERB_SUFFIX':
            case 'PARTICIPLE_SUFFIX':
                if ($switch < 1) {
                    $text .= "</td><td>";
                    $switch++;
                }
                break;
        }

        $text .= isset($value[2]) ? "{$value[0]}" : "{$value[0]} ";
    }
    $text .= "</td></tr>";

    return $text;
}

function output_table(
    $tense,
    $tense_name,
    $style = 'panel-info',
    $num = 3,
    $rows = [0, 1, 2, 3, 4, 5, 6, 7]
) {
    global $data;
    global $verb_conjugator;

    echo "<div class=\"col-md-$num\">";
    echo "<div class=\"panel $style\">";
    echo "<div class=\"panel-heading\">";
    echo "<h3 class=\"panel-title\">" . htmlspecialchars($tense_name) . "</h3>";
    echo "</div>";

    echo "<table class=\"table table-condensed verbs_table\" >";
    foreach ($rows as $i) {
        echo FormatVerb(
            $verb_conjugator->Conjugate($data, [
                "negative" => isset($_REQUEST["negative"]),
                "reflective" => isset($_REQUEST["reflexive"]),
                "tense" => $tense,
                "person" => $i,
            ])
        );
    }
    echo "</table>";

    echo "</div>";
    echo "</div>";
}
?><!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <link rel="shortcut icon" href="./favicon.png">

        <meta property="og:url" content="http://www.ojdip.net/verbete/conjugaison.php?verbe=<?php echo urlencode(
            $verb
        ); ?>"/>
        <meta property="og:title" content="<?php echo htmlspecialchars(
            $verb
        ); ?> - Verbête - Le conjugeur de verbes"/>
        <meta property="og:image" content="http://www.ojdip.net/verbete/favicon.png"/>
        <meta property="og:description" content="La conjugaison du verbe <?php echo htmlspecialchars(
            $verb
        ); ?>"/>

        <title><?php echo htmlspecialchars(
            $verb
        ); ?> - Verbête - Le conjugeur de verbes</title>

        <!-- Bootstrap core CSS -->
        <link href="./css/bootstrap.min.css" rel="stylesheet">
        <link href='http://fonts.googleapis.com/css?family=Lobster' rel='stylesheet' type='text/css'>

        <!-- Custom styles for this template -->
        <link href="./css/site.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="../../assets/js/html5shiv.js"></script>
          <script src="../../assets/js/respond.min.js"></script>
        <![endif]-->

        <script>
            var verb = '<?php echo addslashes($verb); ?>';
        </script>
    </head>

    <body>
        <!-- Static navbar -->
        <div class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#"><span class='text-logofont'>Verbête</span></a>
                </div>
                <div class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <li><a href="index.php#">Page d'accueil</a></li>
                        <li><a href="index.php#about">À propos</a></li>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </div>


        <div class="container">

            <!-- Main component for a primary marketing message or call to action -->

            <div class="panel panel-default">
                <div class="panel-body">
                    <form class="form" role="form" action="conjugaison.php" method="get">
                        <div class="input-group">
                            <input type="text" class="form-control" name="verbe" id="verbeInput" placeholder="Verbe">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-default">Conjuger</button>
                            </span>
                        </div>                 
                    </form>
                </div>
            </div>

            <?php if ($state == States::NON_EXISTANT) { ?>
                <div class="alert alert-danger">
                    Le verbe <em><?php echo $verb; ?></em> n'existe pas.
                </div>
            <?php } else { ?>

                <?php if ($state == States::AD_HOC) { ?>
                    <div class="alert alert-warning">
                        Le verbe <em><?php echo $verb; ?></em> n'a pas été trouvé dans la base de données. 
                        Ainsi, la conjugaison a été faite selon le modèle de verbe <em><?php echo $model; ?></em>.
                    </div>
                <?php } ?>
                <h1>La conjugaison du verbe <em><?php echo $data["prefix"] .
                    $data["infinitive"]; ?></em></h1>

                <div class="btn-group pull-right">
                    <button type="button" id="neg-toggle" class="btn btn-default<?php if (
                        isset($_REQUEST['negative'])
                    ) {
                        echo ' active';
                    } ?>">Forme negative</button>
                    <button type="button" id="ref-toggle" class="btn btn-default<?php if (
                        isset($_REQUEST['reflexive'])
                    ) {
                        echo ' active';
                    } ?>">Forme reflexive</button>
                </div>   

                <h3>Indicatif</h3>

                <div class="row">
                    <?php output_table(
                        Tense::PRESENT_SIMPLE,
                        "Présent simple",
                        'panel-info'
                    ); ?>
                    <?php output_table(
                        Tense::FUTUR_SIMPLE,
                        "Futur simple",
                        'panel-info'
                    ); ?>
                    <?php output_table(
                        Tense::PASSE_SIMPLE,
                        "Passé simple",
                        'panel-info'
                    ); ?>     
                    <?php output_table(
                        Tense::FUTUR_ANTERIEUR,
                        "Futur antérieur",
                        'panel-info'
                    ); ?> 
                </div>

                <div class="row">
                    <?php output_table(
                        Tense::PASSE_COMPOSE,
                        "Passé composé",
                        'panel-info'
                    ); ?>
                    <?php output_table(
                        Tense::IMPARFAIT,
                        "Imparfait",
                        'panel-info'
                    ); ?>
                    <?php output_table(
                        Tense::PLUS_QUE_PARFAIT,
                        "Plus-que-parfait",
                        'panel-info'
                    ); ?>
                    <?php output_table(
                        Tense::PASSE_ANTERIEUR,
                        "Passé anterieur",
                        'panel-info'
                    ); ?>
                </div>

                <div class="row">
                    <div class="col-md-6">   
                        <h3>Conditionel</h3>
                        <div class="row">
                            <?php output_table(
                                Tense::CONDITIONEL_PRESENT,
                                "Conditionel présent",
                                'panel-success',
                                6
                            ); ?> 
                            <?php output_table(
                                Tense::CONDITIONEL_PASSE,
                                "Conditionel passé",
                                'panel-success',
                                6
                            ); ?> 
                        </div>

                    </div>
                    <div class="col-md-6">

                        <h3>Temps périphrastiques</h3>
                        <div class="row">
                            <?php output_table(
                                Tense::FUTUR_PROCHE,
                                "Passé recent",
                                'panel-danger',
                                6
                            ); ?>     
                            <?php output_table(
                                Tense::PASSE_RECENT,
                                "Futur proche",
                                'panel-danger',
                                6
                            ); ?>
                        </div>
                    </div>
                </div>

                <h3>Subjonctif</h3>
                <div class="row">
                    <?php output_table(
                        Tense::SUBJONCTIF_PRESENT,
                        "Subjonctif présent",
                        'panel-warning'
                    ); ?>
                    <?php output_table(
                        Tense::SUBJONCTIF_PASSE,
                        "Subjonctif passé",
                        'panel-warning'
                    ); ?>
                    <?php output_table(
                        Tense::SUBJONCTIF_PQP,
                        "Subjonctif plus-que-parfait",
                        'panel-warning'
                    ); ?>     
                    <?php output_table(
                        Tense::SUBJONCTIF_IMPARFAIT,
                        "Subjonctif imparfait",
                        'panel-warning'
                    ); ?>    
                </div>            

                <hr>

                <div class="row">
                    <?php output_table(
                        Tense::IMPERATIF,
                        "Imperatif",
                        'panel-default',
                        4,
                        [1, 4, 5]
                    ); ?>
                    <?php output_table(
                        Tense::PARTICIPE_PASSE,
                        "Participe passé",
                        'panel-default',
                        4,
                        [2, 3, 6, 7]
                    ); ?>
                    <?php output_table(
                        Tense::PARTICIPE_PRESENT,
                        "Participe présent",
                        'panel-default',
                        4,
                        [0]
                    ); ?>
                </div>
            <?php } ?>

        </div>

        <script src="//code.jquery.com/jquery.js"></script>
        <script src="./js/bootstrap.min.js"></script>
        <script src="./js/typeahead.min.js"></script>
        <script src="./js/code.js"></script>
    </body>
</html>


