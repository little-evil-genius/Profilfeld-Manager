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
Diese Idee stammt von [@aheartforspinach ](https://github.com/aheartforspinach/edit-profile)

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

<img src="https://stormborn.at/plugins/profilfelds_diff.png">
