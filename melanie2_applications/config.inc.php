﻿<?php
/**
 * Plugin Melanie2 Applications
 *
 * plugin melanie2 pour lister et configurer les app de roundcube
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

$rcmail_config['applications_list'] = array();
$rcmail_config['applications_ignore'] = array("button-melanie2_applications");
$rcmail_config['others_applications'] = array(
    array(
        "name" => "My app",
        "internal_url" => "",
        "external_url" => "",
        "internal_icon" => "",
        "external_icon" => "",
    ),
);