=== Klima Monitor ===
Tags: Raspberry Pi, GrovePi+, Wetter, Klima, Temperatur, Luftfeuchtigkeit, Luftdruck
Contributors: mayerst
Requires at least: 3.0.1
Tested up to: 4.2.3
Stable tag: 1.0.2
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Dieses Plugin ermöglicht die Darstellung einer Wettervorhersage, welche mit dem RaspberryPi berechnet wurde. 

== Description ==

Dieses Plugin ermöglicht die Darstellung einer Wettervorhersage, welche mit dem RaspberryPi berechnet wurde. Die notwendigen Daten werden durch Sensoren am GrovePi+ Shield ermittelt. 
Aktuell werden Temperatur, Luftfeuchtigkeit und Luftdruck gemessen. Die gemessenen und berechneten Daten, werden in der Wordpress Datenbank gespeichert und sind durch Liniendiagramme darstellbar.  

Über eine Mail mit dem Einsatzgebiet des Klimamonitors würde ich mich freuen. 

== Installation ==

1. Installiere das Plugin in */wp-content/plugins Verzeichnis
2. Aktiviere das Plugin im "Plugin" Menü von Wordpress, es wird automatisch eine neue Tabelle xxx_climadata angelegt. 
	(xxx = Wordpress prefix
3. Durch drücken der Wetter-Buttons, wird ein Shortcode für ein Liniendiagramm erzeugt:
   [ws_chart title="Heute" chart="temp" day="Today" v_title="Temperatur" width="800px" height="400px" ]
4. Ändere den erzeugten Code nach Deinen Wünschen ab
5. Die Wettervorhersage ist durch den Shortcode [cm_forecast] darstellbar.

		
== Frequently Asked Questions ==

= Was benötige ich für dieses Plugin? =

Du benötigst einen Raspberry Pi, ein GrovePi+ Shield, Temperatur- und Luftdrucksensor.
Ebenso benötigst Du das Coding, mit welchem die Daten in die Datenbank geschrieben werden.
Für mehr Informationen, besuche bitte die Projektseite http://www.2komma5.org 

= An wen kann ich mich wenden, wenn ich Fragen habe? =

Meine eMail Adresse lautet: info@2komma5.org

== Screenshots ==

1. Wettervorhersage

2. Wochenübersicht der Luftfeuchte

3. Wochenübersicht des Luftdrucks

4. Verlauf der Vorhersage

5. Einstellungen

== Changelog ==

= 1.0.0 =
* Initial release

= 1.0.1 =
* Bug fixing: function cm_read_db

= 1.0.2 =
* Bug fixing: Einheit der spez. Luftfeuchte
  
== Einstellmöglichkeiten ==

title   - Definition des Titels z.B.: title="Dies ist ein Titel"

trendline - "yes" , "no"; default ist "no"

chart 	- Definition der Anzeige, z.B.: char="temp"

			temp    - zeigt nur die Temperaturen
			
			temphum - zeigt Temperatur und Luftfeuchte
			
			hum     - zeigt nur die Luftfeuchte
			
			press   - zeigt den Luftdruck
			
			dew		- zeigt den Taupunkt
			
			hums	- zeigt die spez. Feuchte und die Sättigungsfeuchte
			
			forecast - zeigt den Verlauf der Vorhersage

day   	- Definition des Anzeigebereichs, "Today", "Yesterday", "Week", "Month", "Year" 
          z.B.: day="Week" Anzeige der Daten der letzten 7 Tage

v_title - Definition der y-Achsen Beschriftung

Im Bereich Einstellungen-Allgemein steuert ein Flag, das mögliche Löschen der Datenbanktabelle beim Deaktivieren