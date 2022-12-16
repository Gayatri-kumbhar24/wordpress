<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'test' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         '**i=0s2QQLpmQ4Jqlo!|Vh#4_p^Bue]2CK{+ @AafU=>8o,zG$)&hDJDDeV,f`S=' );
define( 'SECURE_AUTH_KEY',  '_;qup}JSP.vJ_*OhuC;m_*gWn;CAS?%s*llVYGYF39ilOX1e:Wtq#eWi.8F&i=Al' );
define( 'LOGGED_IN_KEY',    ')X-kLr5/vwu(%3{=CLPe[v49C:bb72+=(Z`~w8|V& cS9NSS,9MuJLoRQ/Ljqdse' );
define( 'NONCE_KEY',        '0mL8aw fEzboI;L7CXrdB-Xc)1:br!QBUp-r,Ggm`_hp&>mt=pxpu/H$T-EZ8v`n' );
define( 'AUTH_SALT',        '38+#CP;aZ{^kV<gL[K$wbS_pzKOR|uofocPE8OoX;z8clwCmY;SBkhry`<vUnQs;' );
define( 'SECURE_AUTH_SALT', '6DX@+}1,]~GsQ7W,J.l6HP.;fDE<UaQLBiy;&w|Yp~YiYLXZyC1k/_9RkT#t)Cwt' );
define( 'LOGGED_IN_SALT',   '=*aNKH7,S=!@2kh@O~t/E;vCvV2/S*{XhCveL,C/b5WNV;1#),3qC4Zg~pF<8G(&' );
define( 'NONCE_SALT',       'C6 !f0wSJSj;5IRE>{Ly<Oe]khKU;=Jx8XY@usbT!E;06.wik`%z_YU8v/W|Zdw<' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
define('WP_MEMORY_LIMIT', '256M');
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
