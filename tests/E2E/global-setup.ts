import { execSync } from 'child_process';

/**
 * Global setup that ensures required WHMCS data exists before E2E tests run.
 * Seeds currencies (USD, EUR) via direct MySQL if missing.
 */
export default async function globalSetup() {
  const mysqlPassword = process.env.MYSQL_ROOT_PASSWORD || 'smurf5506';
  const mysqlDatabase = process.env.MYSQL_DATABASE || 'whmcs';
  const mysqlContainer = 'whmcs_mysql';

  const mysqlExec = (sql: string) => {
    return execSync(
      `docker exec ${mysqlContainer} mysql -uroot -p${mysqlPassword} ${mysqlDatabase} -e "${sql}"`,
      { encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] }
    );
  };

  // Ensure USD currency exists
  mysqlExec(
    `INSERT INTO tblcurrencies (code, prefix, suffix, format, rate, \\`default\\`) ` +
    `SELECT 'USD', '\\$', ' USD', 1, 1.00000, 0 FROM DUAL ` +
    `WHERE NOT EXISTS (SELECT 1 FROM tblcurrencies WHERE code = 'USD')`
  );

  // Ensure EUR currency exists
  mysqlExec(
    `INSERT INTO tblcurrencies (code, prefix, suffix, format, rate, \\`default\\`) ` +
    `SELECT 'EUR', '€', ' EUR', 3, 1.00000, 0 FROM DUAL ` +
    `WHERE NOT EXISTS (SELECT 1 FROM tblcurrencies WHERE code = 'EUR')`
  );

  console.log('Global setup: currencies seeded');
}
