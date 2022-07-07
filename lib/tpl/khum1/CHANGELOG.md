# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

You can read this changelog in french or in english. English is translated with Deepl. 
I am french, I speak french better than english, so I write in french to be sure to say the good things. English in first ;)

## [2.1.2] - 2020-10-06
### EN
Minor modifications: fixed a few bugs, etc.

#### Added

- Added a CSS rule to prevent titles from being underlined when they are links (case of the blog where it is included in a page).

#### Changed
- Close #12: Default logo changed to "Dokuwiki", in svg as well.
- Minor change: default color of titles in Khaganat purple instead of Khanat blue.
- File _edit.css renamed to _edit.less and cleaned up for more consistency in this part of the css. 
- Modification of the Changelog presentation to read more easily : part EN then part FR.


### Fixed
- Close #18: Fixed the margin bug on the preview and cleaned up the code on this part.
- Fixed an error on the TOC declaration, generating a bug with the tagalert plugin (and probably others): double alert.

### FR
Modifications mineures : corrections de quelques bugs, etc.

#### Added

- Ajout d'une règle CSS pour éviter que les titres soient soulignés lorsque ce sont des liens (cas du blog où c'est inclus dans une page).

#### Changed
- Close #12 : Logo par défaut changé en "Dokuwiki", en svg en plus.
- Modif mineur : couleur par défaut des titres en violet Khaganat et non en bleu Khanat.
- Fichier _edit.css renommé en _edit.less et nettoyage pour plus de cohérence dans cette partie du css. 
- Modification de la présentation du Changelog pour lire plus facilement : partie EN puis partie FR.


### Fixed
- Close #18 : Correction du bug de marge sur la prévisualisation et nettoyage du code sur cette partie.
- Correction d'une erreur sur la déclaration du TOC, générant un bug avec le plugin tagalert (et probablement d'autres) : alerte en double.


## [2.1.1] - 2020-08-30

### EN
This version corrects many small bugs.

#### Changed
- Addition of a margin to the items on the computer. #7 
- Change in the layout of the tables. #8 
- Various small colour changes here and there, including buttons ( #11 ).
- For the wrap plugin ( #2 ) : 
	- background colour of the default box more sober
	- modified border for "round" value
	- responsive: forces the boxes 100% mobile
- Special pages (revisions, administration, editing, etc.) take up the entire screen size, the reading mode remains adapted for comfortable reading. #16
- The search results are more readable. #9

#### Fixed
- There was a concern about page width when comparing the differences between two versions of the history AND that some "words" were long. #15
- Even if the title levels are poorly designed, the summary remains legible. #17
- Changing the way the minimum size of the editing area for articles is taken into account, to avoid having such a high size in other forms.
- 
### FR
Cette version corrige de nombreux petits soucis.

#### Changed
- Ajout d'une marge aux articles sur ordiphone. #7 
- Changement sur la mise en page des tableaux. #8 
- Divers petits changements de couleurs ici et là, dont les boutons ( #11).
- Pour le plugin wrap ( #2 ) : 
	- couleur de fond de la box par défaut plus sobre
	- bordure modifiée pour la valeur "round"
	- responsive : force les box à 100% en mobile
- Les pages spéciales (révisions, administration, édition, etc) prennent toute la taille de l'écran, le mode lecture reste adapté pour une lecture confortable. #16
- Les résultats de recherche sont plus lisibles. #9

#### Fixed
- Il y avait un souci de largeur de page quand on comparait les différences entre deux versions de l'historique ET que certaines "mots" étaient longs. #15
- Même si les niveaux de titre sont mals conçus, le sommaire reste lisible. #17
- Modification de la façon dont la taille minimale de la zone d'édition des articles est prise en compte, afin d'éviter d'avoir une aussi haute taille dans d'autres formulaires.



## [2.1.0] - 2020-06-03

### EN
This version is considered acceptable for production, it meets the requests listed so far. 

#### Added
- Inclusion of footer and general menu header. Just add a symbolic link to the real files :)

#### Removed
- Removal of a css file for testing.

#### Changed
- "childpage" plugin updated , finishing of its cosmetics
- Improved integration of the "translation" plugin.
- Homogenization of colors. All css colors are variables in the ini file.
- "Back to Top" transformed into an icon, without sacrificing accessibility if all is good.
- Various modifications of details on the css (height, width, margins, etc).
- Modification of the display of links: more consistency, everything is "underlined" (no underlining via borders). 
- The buttons to fold/unfold the sidebar and the table of contents are bigger and better placed: more visible, easier to handle.
- Modification of the default height of the editing area (80% of the screen height). 

#### Fixed
- Fixed a bug on the tabs when the loaded fonts are not the right ones.
- The table of contents is displayed again when previewing articles.
- The articles take all the width when previewing, there is no more strange margin on the right. 
- The "article" part keeps the same width between reading and editing.

### FR

Cette version est considérée comme acceptable pour la prod, elle répond aux demandes listées jusque là. 

#### Added
- Inclusion d'un pied de page et d'un menu d'en-tête général. Il suffit d'ajouter un lien symbolique vers les fichiers réels :)

#### Removed
- Enlèvement d'un fichier css pour les tests.

#### Changed
- Plugin "childpage" mis à jour, finition de sa cosmétique
- Amélioration de l'intégration du plugin "translation".
- Homogénéisation des couleurs. Toutes les couleurs css sont des variables dans le fichier ini.
- "Retour en Haut de page" transformé en icone, sans sacrifier à l'accessibilité en principe.
- Diverses modifications de détails sur le css (hauteur, largeur, marges, etc).
- Modification de l'affichage des liens : plus de cohérence, tout est "souligné" (pas de soulignement via des bordures). 
- Les boutons pour plier/déplier la sidebar et le sommaire sont plus grosses et mieux placées : plus visibles, plus faciles à manipuler.
- Modification de la hauteur par défaut de la zone d'édition (80% de la hauteur de l'écran). 

#### Fixed
- Correction d'un bug sur les onglets lorsque les polices chargées ne sont pas les bonnes.
- Le sommaire s'affiche à nouveau lors de la prévisualisation des articles.
- Les articles prennent toutes la largeur lors de la prévisualisation, il n'y a plus de marge étrange à droite. 
- La partie "article" garde la même largeur entre la lecture et l'édition.

## [2.0.0] - 2020-04-09
### EN
This version reaches a certain maturity but needs some fine-tuning before being put into production. 

#### Added
- Consideration of accessibility. Navigation seems acceptable with a screen reader, contrasts are good enough. 
- Work on the responsive aspect. The site should be readable on all monitors. 

#### Changed
- General design reviewed, several times. 

#### Removed
- Removed tab management (vv222 create a dedicated plugin). 

### FR
Cette version atteint une certaine maturité mais a besoin de peaufinage avant d'être mise en prod. 

#### Added
- Prise en compte de l'accessibilité. La navigation semble acceptable avec un lecteur d'écran, les contrastes suffisament bons. 
- Travail sur l'aspect responsive. Le site devrait être lisible sur tous les moniteurs. 

#### Changed
- Design général revu, plusieurs fois. 

#### Removed
- Suppression de la gestion des onglets ( vv222 a créé un plugin dédié). 



## [1.0.0] - 2018-02-17 [YANKED]
### EN

#### Added
- First implementation of Khum1. It seemed functional, but it had a lot of bugs in production. It was abandoned after a few months of testing. 

### FR
#### Added

- Première implémentation de Khum1. Il semblait fonctionnel, mais il avait beaucoup de bug en prod. Il a été abandonné après quelques mois de test.