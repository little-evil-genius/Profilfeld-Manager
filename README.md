# Profilfeld-Manager
Das Plugin erweitert die Verwaltung der Profilfeldern im ACP & UserCP und in deren Ausgabe im Forum.<br>
Teammitglieder können im ACP zusätzliche Seiten für das UserCP erstellt. Auf diesen Seiten können Mitglieder - ähnlich wie auf der klassischen Profil-Seite - ihre Profilfelder bearbeiten. Dadurch lassen sich Profilfelder übersichtlich auf mehrere Bereiche aufteilen und besser strukturieren<br>
<br>
Beim Erstellen und Bearbeiten von Profilfeldern können die erweiterten Einstellungen festgelegt werden. Unter anderem auf welcher Seite ein Feld angezeigt wird. Zur Auswahl steht standardmäßige Profil-Seite oder eine der individuell erstellten Seiten.<br>
Eine weitere Funktion ist die Möglichkeit, Abhängigkeiten zwischen Profilfeldern zu definieren. Das bedeutet, dass ein Feld von einem anderen Feld abhängig ist und im UserCP nur angezeigt wird, wenn in dem referenzierten Feld einer der definierten Werte ausgewählt wurde. Zur Auswahl stehen nur Felder mit festen Auswahlmöglichkeiten. Es können ein oder mehrere Abhängigkeitswerte festgelegt werden für ein Feld. Ein Feld kann für mehrere Felder das Referenz Feld sein.<br>
<br>
Für jedes Profilfeld kann ein Standardinhalt definiert werden. Dieser wird angezeigt, wenn das Feld noch nicht ausgefüllt wurde. So können Platzhalter dargestellt werden. Zusätzlich kann ein separater Inhalt für Gäste festgelegt werden. Das bedeutet, dass das Profilfeld vor Gäste verborgen wird und stattdessen der alternativer Text angezeigt wird.<br>
<br>
Die letzte Funktion ist die Option von der Überprüfung von bearbeitenden Profilfeldern. Dies kann für jedes Profilfeld individuell festgelegt werden. Dies bedeutet, wird ein entsprechendes Profilfeld bearbeitet, wird die Änderung zunächst nicht direkt übernommen im Forum. Stattdessen wird der neue Inhalt seperat gespeichert und wartet auf die Überprüfung vom Team.
Teammitglieder können parallel den alten und neuen Inhalt vergleichen und dann annehmen oder ablehnen. Bei einer Annahme bekommt das Mitglied ein Alert und der neue Inhalt für das Profilfeld wird angezeigt. Wird die Änderung abgelehnt muss das Teammitglied eine Begründung mitgeben und das Mitglied bekommt eine PN.
Diese Idee stammt von [@aheartforspinach](https://github.com/aheartforspinach/edit-profile)

## Eigene UserCP-Seiten
Mit diesem Plugin können ganz einfach zusätzliche Seiten für das UserCP erstellt werden. Dadurch werden nicht alle Profilfelder auf einer einzigen Seite (Profile) angezeigt, sondern werden auf mehrere Bereiche aufgeteilt - zum Beispiel "Allgemeines", "Aussehen" oder "Schulisches".<br>
Die Erstellung einer neuen Seite erfolgt über ein simples Formular im ACP.<br>
- <b>Seitenbezeichnung:</b> Der Titel für die Seite, der später im Browser-Tab und auf der Seite selbst angezeigt wird.<br>
- <b>Identifikator/Link:</b> Dieser dient für die individuelle Bezeichnung im URL - usercp.php?action=xxx. Wichtig ist dabei, dass dieser keine Leerzeichen oder Sonderzeichen enthält, da er maschinenlesbar sein muss.<br>
- <b>Navigation:</b> Dies ist nur der Anzeigetext in der Navigation des UserCP.<br>
Die erstellten Seiten lassen sich nicht nur über die Navigation erreichen, sondern können auch direkt über den Link aufgerufen oder manuell eingebunden werden.

### eigene Templates für die Seiten
Standardmäßig nutzen alle eigenen Seiten dasselbe Template. Es ist möglich für jede Seite ein eigenes Template anzulegen. So hat man die Möglichkeit, jede Seite individuell zu gestalten und genau an deine Bedürfnisse anzupassen. Wichtig ist dabei nur, dass die grundlegende Formularstruktur erhalten bleibt, damit das Speichern der Inhalte weiterhin funktioniert.

### für Fortgeschrittene
Zusätzlich gibt es die Möglichkeit, die Profilfelder im Formular gezielt anzusprechen und frei im Template zu platzieren. Dabei sollte man sich allerdings entscheiden, ob man mit den vorhandenen Feld-Blöcken arbeitet oder die Felder einzeln einbindet, da es sonst zu doppelten Anzeigen kommen kann.
Wenn ihr ein neues Profilfeld hinzufügt, dann müsst ihr es auch manuell im Template einfügen.<br>
<br>
Feld-Blöcke: {$requiredfields} {$customfields}<br>
individuell: {$fields['fidXX']}

## Codebuttons im UserCP
Sobald für ein Profilfeld HTML und/oder MyCode erlaubt ist, werden automatisch die bekannten Codebuttons eingeblendet - genau so, wie man sie auch aus dem Beitragseditor kennt. Statt sich Codes merken zu müssen, können sie einfach auf die entsprechenden Buttons klicken, um zum Beispiel Text fett oder kursiv darzustellen, Links einzufügen oder andere Formatierungen vorzunehmen.<br>
Sollte der [markItUp! Editor](https://www.mybb.de/erweiterungen/18x/plugins-verschiedenes/markitup-editor-fuer-mybb/) von StefanT installiert sein greift die Darstellung auf diesen zurück.

## Abhängigkeiten zwischen Profilfeldern
Mit dieser Funktion kann festgelegt werden, dass bestimmte Profilfelder nur dann sichtbar sind, wenn zuvor eine passende Auswahl in einem anderen Feld getroffen wurde. Dadurch lassen sich unnötige oder unpassende Felder automatisch ausblenden.<br>
Ein einfaches Beispiel: Es gibt ein Feld "Geschlecht" mit den Auswahlmöglichkeiten "weiblich", "männlich" und "non-binär". Zusätzlich gibt es ein weiteres Feld "Geschlechtsbezeichnung". Dieses Feld wird nur dann angezeigt, wenn im Feld "Geschlecht" die Option "non-binär" ausgewählt wurde. In allen anderen Fällen bleibt es ausgeblendet.<br>
<br>
Die Abhängigkeiten funktionieren dabei nicht nur innerhalb einer einzelnen Seite, sondern auch seitenübergreifend. Das bedeutet: Wenn auf der UserCP-Seite "Allgemeine Informationen" bei dem Feld "Gruppe" der Wert "Schüler:in" ausgewählt wird, kann dadurch auf einer anderen Seite die Felder sichtbar werden, welche nur für Schüler:innen relevant sind.<br>
Außerdem berücksichtigt das System auch verschachtelte Abhängigkeiten. Wenn ein Feld also selbst wieder von einem anderen Feld abhängig ist, wird diese zusätzliche Bedingung ebenfalls geprüft.<br>
Die Abhängigkeit bezieht sich nur auf das UserCP - nicht auf die Ausgabe im Forum.

## Ausgabe der Profilfelder im Forum
Im Forum kommt es häufig vor, dass user:innen ihre Profilfelder noch nicht oder nur teilweise ausgefüllt haben. Damit Profile und Beiträge trotzdem ordentlich und vollständig wirken, können für jedes Profilfeld einen eigenen Platzhalter festgelegt werden. Dieser wird automatisch angezeigt, wenn ein Feld leer ist.<br>
Dieser Platzhalter greift übrigens auch dann, wenn Gäste Beiträge verfassen.<br>
<br>
Zusätzlich kann für jedes Profilfeld festgelegt werden, ob Gäste den tatsächlichen Inhalt sehen dürfen oder nicht. Dabei spielt es keine Rolle, ob das Feld ausgefüllt ist oder nicht. Wenn diese Option aktiviert ist, wird Gästen statt des echten Inhalts ein alternativer Text angezeigt. Das ist besonders praktisch bei sensibleren Informationen, wie zum Beispiel detaillierten Charakterbeschreibungen, die nur für registrierte User:innen sichtbar sein sollen.<br>
<br>
Beide Optionen - also Platzhalter und Gastanzeige . lassen sich individuell für jedes einzelne Profilfeld einstellen. Du hast damit die volle Kontrolle darüber, wie Inhalte im Forum dargestellt werden und wer sie sehen kann. Auch ist für beide HTML und BBCode möglich.<br>
Auch werden Zeilenumbrüche nun automatisch erkannt und es müssen keine <br> mehr hinterlegt werden.<br>
Die bestehenden Standard-Variablen von MyBB werden automatisch überschrieben. Die Ausgabe funktioniert im Memberprofil, im Postbit (egal ob normale Beiträge, private Nachrichten, Vorschau oder Ankündigungen), in der Mitgliederliste sowie global im gesamten Forum.<br>
- Memberprofil: {$userfields['fidX']} & {$memprofile['fidX']}
- Postbit: {$post['fidX']}
- Mitgliederliste: {$user['fidX']}
- Global: {$mybb->user['fidX']}

### Funktion für eigene Codes und Plugins
Wenn die erweiterten Profilfelder Ausgabe in eigenen Plugins oder Codes verwendet werden soll, musst man zunächst das Plugin einbinden, damit man anschließend die entsprechende Funktion mit der passenden User-ID aufrufen/ausführen kann. Wichtig ist dabei, dass immer die UID des jeweiligen Accounts übergiben wird, dessen Profilfelder anzeigt werden sollen.<br>
<br>
Ein einfaches Grundbeispiel sieht so aus:
```php
require_once MYBB_ROOT . 'inc/plugins/profilefields_manager.php';

// Wichtig: Hier muss die UID des Accounts stehen
$uid = $user['uid']; // Beispiel – je nach Kontext anpassen!

$fields = profilefields_manager_build_view($uid);
```
Die Variable $uid musst du dabei an den jeweiligen Code angepasst werden. In manchen Fällen kommt sie zum Beispiel aus $user, $post, $memprofile oder aus einer eigenen Datenabfrage.<br>
Nachdem die Funktion aufgerufen wurde, stehen alle Profilfelder bereits fertig aufbereitet im Array $fields zur Verfügung. Das bedeutet, dass Platzhalter, Gast-Anzeige und alle weiteren Regeln schon berücksichtigt sind.<br>
Es können die Werte dann direkt einzeln verwenden, zum Beispiel so:
```php
$fields['fid1'];
$fields['fid2'];
```
Alternativ kann man - und das ist in vielen Fällen die bessere Lösung - die Felder mit deinem bestehenden Ausgabe-Array zusammenführen. Dadurch können weiterhin die gewohnten Variablen verwendet werden, ohne die Template anpassen zu müssen.<br>
Ein Beispiel dafür wäre:
```php
$memprofile = array_merge($memprofile, $fields);
```
Dieses Vorgehen bietet sich besonders an, wenn bereits mit Arrays wie $memprofile, $post oder $user gearbeitet wird oder innerhalbe einer eigenen Schleife (while). Durch das Zusammenführen werden die bestehenden Werte automatisch erweitert bzw. überschrieben, sodass überall die korrekten Inhalte angezeigt werden.

## Überprüfung von Änderungen an Profilfeldern
Mit dieser Funktion kann festgelegt werden, dass Änderungen an bestimmten Profilfeldern nicht sofort sichtbar werden, sondern erst vom Team geprüft werden müssen. Das ist besonders praktisch bei schon gewobbten Charakteren/angenommenen Steckbriefen. Diese Funktion lässt sich für jedes Profilfeld und Gruppe individuell aktivieren.<br>
Wenn ein Mitglied ein entsprechendes Profilfeld bearbeitet, wird die Änderung zunächst im Hintergrund gespeichert. Der bisherige Inhalt bleibt weiterhin im Forum sichtbar. Die neue Version wird also nicht direkt übernommen, sondern wartet auf eine Freigabe durch das Team.<br>
Über ein Banner auf dem Index wird das Team entsprechend über eine Überprüfung informatiert. Im ModCP können die Änderungen dann überprüft werden. Dabei werden der alte und der neue Inhalt gegenübergestellt, sodass Unterschiede schnell erkennbar sind. Anschließend kann entschieden werden, ob die Änderung angenommen oder abgelehnt wird.<br>
Wird die Änderung akzeptiert, ersetzt der neue Inhalt automatisch den alten. Das Mitglied erhält zusätzlich eine Benachrichtigung darüber, dass die Änderung übernommen wurde. Wird die Änderung hingegen abgelehnt, bleibt der ursprüngliche Inhalt bestehen. Gleichzeitig muss das Teammitglied eine Begründung angeben, die per PN mitgeteilt wird.

# Vorrausetzung
- Das ACP Modul <a href="https://github.com/little-evil-genius/rpgstuff_modul" target="_blank">RPG Stuff</a> <b>muss</b> vorhanden sein.
- Der <a href="https://github.com/frostschutz/MyBB-PluginLibrary" target="_blank">PluginLibrary for MyBB</a> von frostschutz <b>muss</b> installiert sein.

# Datenbank-Änderungen
### hinzugefügte Tabellen:
- usercp_pages
- profilefields_edit

### hinzugefügte Spalten in profilefields:
- usercp_page
- dependenceFID
- dependencecontent
- defaultcontent
- guestpermissions
- guestcontent
- editcheck

# Core-Datei-Änderungen
Das Plugin verändert automatisch drei Stellen in der <b>usercp.php</b>.

# Neue Sprachdateien
- deutsch_du/admin/profilefields_manager.lang.php
- deutsch_du/profilefields_manager.lang.php

# Neue Template-Gruppe innerhalb der Design-Templates
- Profilfeld-Manager

# Neue Templates (nicht global!)
- profilefieldsmanager_banner
- profilefieldsmanager_modcp
- profilefieldsmanager_modcp_bit
- profilefieldsmanager_modcp_edit
- profilefieldsmanager_modcp_nav
- profilefieldsmanager_modcp_popup
- profilefieldsmanager_usercp_menu
- profilefieldsmanager_usercp_menu_bit
- profilefieldsmanager_usercp_page

# Neue Variablen
- header: {$profilefields_manager_banner}
- modcp_nav_users: {$nav_profilefields_manager}

# Template Änderungen
### Kleinere Änderungen:
- usercp_profile_profilefields_textarea ({$codebuttons})
- usercp_profile (Script Tag)

### Größere Änderungen:
- usercp_profile_profilefields_checkbox
- usercp_profile_profilefields_multiselect
- usercp_profile_profilefields_radio
- usercp_profile_profilefields_select
- usercp_profile_customfield

# Neues CSS - profilefields_manager.css
Es wird automatisch in jedes bestehende und neue Design hinzugefügt. Man sollte es einfach einmal abspeichern - auch im Default. Nach einem MyBB Upgrade fehlt der Stylesheets im Masterstyle? Im ACP Modul "RPG Erweiterungen" befindet sich der Menüpunkt "Stylesheets überprüfen" und kann von hinterlegten Plugins den Stylesheet wieder hinzufügen.
```css
.pfm-diff-added {
        background: #d9f2d9;
        color: #1f5f1f;
        padding: 1px 2px;
        border-radius: 3px;
        }

        .pfm-diff-removed {
        background: #f8d7da;
        color: #842029;
        padding: 1px 2px;
        border-radius: 3px;
        text-decoration: line-through;
        }

        .pfm-diff-same {
        background: transparent;
        
```

# Benutzergruppen-Berechtigungen setzen
Damit alle Admin-Accounts Zugriff auf die Verwaltung der UserCP-Seiten haben im ACP, müssen unter dem Reiter Benutzer & Gruppen » Administrator-Berechtigungen » Benutzergruppen-Berechtigungen die Berechtigungen einmal angepasst werden. Die Berechtigungen befinden sich im Tab 'RPG Erweiterungen'.

# Links
### ACP
<b>Reservierungstypen</b><br>
index.php?module=rpgstuff-profilefields_manager

### Forum
<b>Eigene Seite</b><br>
usercp.php?action=xxx

# Demo
<img src="https://stormborn.at/plugins/profilfelds_page.png">
<img src="https://stormborn.at/plugins/profilfelds_page_add.png">

<img src="https://stormborn.at/plugins/profilfelds_fields.png">

<img src="https://stormborn.at/plugins/profilfelds_codebuttons.png">

<img src="https://stormborn.at/plugins/profilfelds_diff.png">
