# WP-MyClubWebAPI-ShowEventsWithTeamName
Wordpress plugin code with shortcode to fetch myclub data via web api (https://www.myclub.fi/api/public.html)

MyClub GetEvents shortcode
shortcode [get_myclub_events_via_groupname url="https://<club>.myclub.fi/api/events/?group_id=" name="<Group/Team name>" amount="8"]
Place Clubs Api Key into file: myclubapi.key (Api key can be asked and generated from http://myclub.fi support)
NOTE! as the key can be used to edit/create API services, make sure that your web server configured to prevent showing *.key file content!
To check is your key file publicly visible, try browsing directly it: www.yourwebsite.fi/wp-content/plugins/myclub-show-events-plugin/myclubapi.key

TODO: Make WP plugin around this. Now you can just add this to some existing plugin php file.
