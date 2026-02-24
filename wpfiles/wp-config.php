<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mskotoi7_seek' );

/** Database username */
define( 'DB_USER', 'mskotoi7_seek' );

/** Database password */
define( 'DB_PASSWORD', 'vIVvV&H5sY3d' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );


define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // не выводить ошибки в браузер (только в wp-content/debug.log)
// Принудительно отключаем вывод в браузер (WP 6.7 + ACF: Notice ломает заголовки и админку)
if (function_exists('ini_set')) {
    @ini_set('display_errors', '0');
}



/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '%N3Wg4yn4DXO+U4]dNQh>7iWW.W}~vcQTZhw!;<_O!KYH`B&[zcl!&e=y&kQ]2}t' );
define( 'SECURE_AUTH_KEY',  '}<Sl#RSmh6vxiibXJ|9|i7yfXzc3OdKWez5VoUR1M+KFt*5`OU2,9/gQhwQv=[RI' );
define( 'LOGGED_IN_KEY',    '?~2SbNR~82s8YJC_yVmW`HmbJm 2-)ylv}%w_y2i:eG%f[rzqc1-{BDTQ L}kS?$' );
define( 'NONCE_KEY',        '9<1d~f=_QTm9Xta.Bn.^{=^ntGt{$-NAeurb1zfrq6zWA3-#~.7LG95MTB|c1q D' );
define( 'AUTH_SALT',        ',~If*,B[.=@3  z dvJ^4<s5!e3j5[j`{D;UBuf{q:TZHA+RZ6/TJ3+t.kGs%.bx' );
define( 'SECURE_AUTH_SALT', '|sTgm4uwCY}XEQ/yfeJ*Ft0/{ifqt0zjps5689r}U rRCSi<a{#Y^rZ&Gw8Na#T-' );
define( 'LOGGED_IN_SALT',   '1?_]vt,19BG8 nYxh$W~ihY:)${:#r>Rs}L>;i<=T!sG$Nh;b`=l+R90P?.4{W &' );
define( 'NONCE_SALT',       'B~ R^:{8WpED9eWr`0G_X$A0plm#XiO=T<G<rL&v8|jD}a&LH),b#kWO{c3$+|v/' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */






/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
