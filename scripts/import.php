<?php
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

$categories = [
    'infinitive' => ['infinitive-present'],
    'indicative' => ['present', 'imperfect', 'future', 'simple-past'],
    'conditional' => ['present'],
    'subjunctive' => ['present', 'imperfect'],
    'imperative' => ['imperative-present'],
    'participle' => ['present-participle', 'past-participle'],
];

$replace_array = [
    'infinitive-present' => '',
    'present' => '_present',
    'imperfect' => '_imperfect',
    'future' => '_future',
    'simple-past' => '_past',
    'imperative-present' => '',
    'present-participle' => '_present',
    'past-participle' => '_past',
];

function build_query($db_connection, $name, $data)
{
    global $template_columns;
    $field_names[] = 'name';
    $field_values[] = $db_connection->quote($name);
    foreach ($data as $element_name => $element) {
        if (count($element) == 1) {
            $field_names[] = "$element_name";
            if ($element[0] === null) {
                $field_values[] = 'NULL';
            } else {
                $field_values[] = $db_connection->quote($element[0]);
            }
        } else {
            foreach ($element as $id => $value) {
                $field_names[] = "{$element_name}_{$id}";

                if ($value === null) {
                    $field_values[] = 'NULL';
                } else {
                    $field_values[] = $db_connection->quote($value);
                }
            }
        }
    }
    $template_columns = $field_names;
    return "INSERT INTO `templates` (" .
        implode(', ', $field_names) .
        ") VALUES (" .
        implode(', ', $field_values) .
        ");";
}

function add_meta($a)
{
    return "$a VARCHAR(30) NULL";
}

function build_tables($db_connection)
{
    global $template_columns;
    if (count($template_columns) == 0) {
        die('Template columns not yet initialized.');
    }

    $db_connection->query("`DROP TABLE IF EXISTS templates;");
    $db_connection->query("`DROP TABLE IF EXISTS verbs;");
    $new = array_map(add_meta, $template_columns);
    $query =
        "CREATE TABLE templates (" .
        implode(" ,", $new) .
        ") COLLATE utf8_general_ci;";
    $db_connection->query($query);

    $query =
        "CREATE TABLE verbs (verb VARCHAR(30) NOT NULL," .
        "template VARCHAR(30) NOT NULL," .
        "prefix VARCHAR(30) NOT NULL," .
        "aspirate_h BOOL NOT NULL," .
        "reflexive BOOL NOT NULL) COLLATE utf8_general_ci;";
    $db_connection->query($query);
}

$xml = simplexml_load_file('conjugation-fr.xml');
foreach ($xml->template as $template) {
    $template_name = $template['name'];
    unset($parsed); # We reset the current parsed data
    foreach ($categories as $main_cat => $sub_cat) {
        foreach ($sub_cat as $value) {
            $counter = 0;

            foreach ($template->{$main_cat}->{$value}->p as $current) {
                $last = null;

                foreach ($current->i as $__last) {
                    $last = $__last;
                }

                if ($last !== null) {
                    $last = (string) $last;
                }
                $parsed[$main_cat . $replace_array[$value]][$counter] = $last;
                $counter++;
            }
        }
    }
    $query = build_query($db_connection, $template_name, $parsed);
    echo("Executing $query\n");
    if (!$generated_tables) {
        build_tables($db_connection);
        $generated_tables = true;
    }
    $db_connection->query($query);
}

# ----------------------------------------------------------------------------#

$xml = simplexml_load_file('verbs-fr.xml');
foreach ($xml->v as $verb) {
    $query =
        'INSERT INTO `verbs` (verb, template, prefix, aspirate_h, reflexive) VALUES (' .
        $db_connection->quote($verb->i) .
        ',' .
        $db_connection->quote($verb->t) .
        ',' .
        $db_connection->quote(
            substr($verb->i, 0, -(strlen($verb->t) - strpos($verb->t, ":") - 1))
        ) .
        ', ' .
        (int) isset($verb->{'aspirate-h'}) .
        ', 0);';
    echo("Executing $query\n");
    $db_connection->query($query);
}
?>
