<?php
require_once('./include/lists.inc.php');
?><!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">
        <link rel="shortcut icon" href="./favicon.png">

        <meta property="og:url" content="http://www.ojdip.net/verbete/"/>
        <meta property="og:title" content="Verbête - Le conjugeur de verbes"/>
        <meta property="og:image" content="http://www.ojdip.net/verbete/favicon.png"/>
        <meta property="og:description" content="Verbête est un outil qui vous permet de voir les conjugaisons des verbes français d'une manière très facile. "/>

        <title>Verbête - Le conjugeur de verbes</title>

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
            <div class="jumbotron">
                <h1 class="text-logofont">Verbête</h1>
                <p>Voulez-vous voir la conjugaison d'un verbe? Allez-y, essayez:</p>

                <div class="fix_line_height">
                    <form class="form-inline" role="form" action="conjugaison.php" method="get">
                        <div class="input-group">
                            <input type="text" class="form-control" name="verbe" id="verbeInput" placeholder="Verbe">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-default">Conjuger</button>
                            </span>
                        </div>                 
                    </form>
                </div>

            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="panel panel-default">
                        <div class="panel-heading h3 text-logofont" id="about">À propos</div>
                        <div class="panel-body">        

                            <p class="lead">
                                Verbête est un outil qui vous permet de voir les conjugaisons des verbes français d'une manière très facile. 
                            </p>
                            <p>
                                La basse de donnes comporte plus de 7000 verbes differents et leurs conjugaisons en tous les temps principaux (present, futur simple, passe simple, conditionel, subjonctif, subjonctif imparfait et biensûr les temps composés et temps périphrastiques qui en sont dérivés).               
                            </p>
                            <p>
                                La source des conjugasions est le logicel <a href='http://sarrazip.com/dev/verbiste.html'>Verbiste</a>, crée par Pierre Sarazin. Cette application Web a été créée par <a href='http://www.ojdip.net'>Tibor Djurica Potpara</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading h3 text-logofont" id="verbes-communs">Verbes communs</div>
                        <table class="table table-condensed verbs_table" >
                            <?php
                            $verb_list = VerbLists::$VerbesCommuns;
                            shuffle($verb_list);

                            foreach (array_slice($verb_list, 0, 8) as $verb) {
                                echo "<tr><td><a href=\"conjugaison.php?verbe=" . urlencode($verb) . "\">$verb</a></td></tr>";
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <script src="//code.jquery.com/jquery.js"></script>
        <script src="./js/bootstrap.min.js"></script>
        <script src="./js/typeahead.min.js"></script>
        <script src="./js/code.js"></script>
    </body>
</html>

