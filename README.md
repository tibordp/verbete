## Verbête

Verbête est une application Web qui vous permet de voir les conjugaisons des verbes français d'une manière très facile.

La basse de données comporte plus de 7000 verbes differents et leurs conjugaisons en tous les temps principaux (present, futur simple, passe simple, conditionel, subjonctif, subjonctif imparfait et biensûr les temps composés et temps périphrastiques qui en sont dérivés).

##  License

Ce application Web est un logiciel libre; vous pouvez le redistribuer sous les termes de la GNU General Public License. Ce programme ne vient avec absolument aucune garantie. Voir le fichier [COPYING](./COPYING) (en anglais) pour les détails.

La source des conjugasions (`conjugation-fr.xml`, `verbs-fr.xml`) est le logicel [Verbiste](http://sarrazip.com/dev/verbiste.html), crée par Pierre Sarazin.

## Utilisation

Lancez Verbête comme ceci:

```bash
docker-compose up
```

Au démarrage, la base de données MySQL sera initialisée avec le contenu des fichiers `conjugation-fr.xml` et `verbs-fr.xml`.

Verbête sera donc disponile sur http://localhost:8100/
