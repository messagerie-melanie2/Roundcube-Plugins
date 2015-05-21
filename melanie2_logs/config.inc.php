<?php
/**
 * Plugin Melanie2_logs
 *
 * plugin melanie2_logs pour roundcube
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

// Nom du fichier où doivent être écrite les logs
$rcmail_config['log_file'] = 'roundcube_melanie2.log';


// Configuration du niveau de log souhaité pour le plugin melanie2_logs
// Possibilité : TRACE, DEBUG, INFO, ERROR, WARN
// Format :
//     - DEBUG|INFO|ERROR|WARN
//     - TRACE|DEBUG|INFO|ERROR|WARN
//     - INFO|ERROR|WARN
// TRACE : Utilisé pour le developpement pour ajouter des traces dans le code
// DEBUG : Utilisé pour le debugage, permet de lister les appels et les hooks
// INFO : Utilisé en production, affiche les informations importantes
// ERROR : Affiche les erreurs d'execution du code qui empeche le bon déroulement du code
// WARN : Affiche des warning sur des erreurs qui n'empeche pas le bon déroulement du code
$rcmail_config['melanie2_logs_level'] = 'TRACE|DEBUG|INFO|ERROR|WARN';