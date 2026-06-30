const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');

const adminUsername = process.env.BROWSER_ADMIN_USERNAME || 'admin';
const adminPassword = process.env.BROWSER_ADMIN_PASSWORD || '123456';
const scopedUsername = process.env.BROWSER_SCOPED_USERNAME || 'scoped';
const scopedPassword = process.env.BROWSER_SCOPED_PASSWORD || '123456';
const seededSiteId = process.env.BROWSER_SITE_ID || '1';
const restrictedSiteId = process.env.BROWSER_RESTRICTED_SITE_ID || '2';
const baseURL = process.env.BROWSER_BASE_URL || 'http://127.0.0.1:8000';

test.use({ baseURL });

async function readXlsxText(response, filename) {
  const outputDirectory = fs.mkdtempSync(path.join(os.tmpdir(), 'camr-xlsx-'));
  const outputPath = path.join(outputDirectory, filename);
  fs.writeFileSync(outputPath, await response.body());

  return JSON.parse(execFileSync('php', [
    path.join(__dirname, 'read-xlsx-text.php'),
    outputPath,
  ], { encoding: 'utf8' }));
}

function expectWorkbookText(values, expectedValues) {
  const workbookText = values.join('\n');

  for (const expectedValue of expectedValues) {
    expect(workbookText).toContain(expectedValue);
  }
}

async function expectNoPageErrors(page) {
  const errors = [];
  const ignoredConsoleErrors = [
    'Failed to load resource: net::ERR_NAME_NOT_RESOLVED',
  ];

  page.on('pageerror', error => {
    errors.push(error.message);
  });

  page.on('console', message => {
    if (message.type() === 'error' && !ignoredConsoleErrors.includes(message.text())) {
      errors.push(message.text());
    }
  });

  return errors;
}

async function loginAsAdmin(page) {
  await page.goto('/');
  await page.locator('#user_name').fill(adminUsername);
  await page.locator('#InputPassword').fill(adminPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/site$/);
}

async function loginAsScopedUser(page) {
  await page.goto('/');
  await page.locator('#user_name').fill(scopedUsername);
  await page.locator('#InputPassword').fill(scopedPassword);
  await page.getByRole('button', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/site$/);
}

async function openGatewayUploadModal(page) {
  const gatewayResponsePromise = page.waitForResponse(response =>
    response.url().includes('/getGatewayPerBLGandEEROOM') && response.status() === 200
  );

  await loginAsAdmin(page);
  await page.goto(`/site_details/${seededSiteId}`);
  await gatewayResponsePromise;

  await page.locator('#sitegatewaylist-tab').click();
  await expect(page.locator('#gatewaylist')).toBeVisible();
  await expect(page.locator('#UploadGatewayMeter').first()).toBeVisible();

  await page.locator('#UploadGatewayMeter').first().click();
  await expect(page.locator('#UploadGatewayMeterModal')).toBeVisible();
  await expect(page.locator('#view_serial_number_upload')).toContainText('GW-SN-001');
  await expect(page.locator('#import_gateway_idx')).toHaveValue('1');
}

async function openRawReportParameters(page) {
  await loginAsAdmin(page);
  await page.goto('/raw_report');

  await expect(page.getByRole('heading', { name: 'RAW Report' })).toBeVisible();
  await page.getByRole('button', { name: /Report Parameters/ }).click();
  await expect(page.locator('#GenerateRAWReportModal')).toBeVisible();
}

async function fillRawReportParameters(page) {
  const meterListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/generate_meter_list') && response.status() === 200
  );

  await page.locator('#site_id').fill('SITEA - Accessible Building');
  await page.locator('#site_id').dispatchEvent('change');
  await meterListResponsePromise;
  await expect(page.locator('#meter_list option[value="MTR-001"]')).toHaveCount(1);

  await page.locator('#meter_id').fill('MTR-001');
  await page.locator('#start_date').fill('2026-01-01');
  await page.locator('#start_time').fill('00:00');
  await page.locator('#end_date').fill('2026-01-01');
  await page.locator('#end_time').fill('01:00');
}

async function openSiteReportParameters(page) {
  await loginAsAdmin(page);
  await page.goto('/site_report');

  await expect(page.getByRole('heading', { name: 'Building Report' })).toBeVisible();
  await page.getByRole('button', { name: /Report Parameters/ }).click();
  await expect(page.locator('#GenerateSAPReportModal')).toBeVisible();
}

async function fillSiteReportParameters(page) {
  await page.locator('#site_id').fill('SITEA - Accessible Building');
  await page.locator('#meter_role').selectOption('');
  await page.locator('#start_date').fill('2025-12-31');
  await page.locator('#start_time').fill('23:59');
  await page.locator('#end_date').fill('2026-01-01');
  await page.locator('#end_time').fill('01:01');
}

async function openSapReportParameters(page) {
  await loginAsAdmin(page);
  await page.goto('/sap_report');

  await expect(page.getByRole('heading', { name: 'SAP Report' })).toBeVisible();
  await page.getByRole('button', { name: /Report Parameters/ }).click();
  await expect(page.locator('#GenerateSAPReportModal')).toBeVisible();
}

async function fillSapReportParameters(page) {
  await page.locator('#site_id').fill('SITEA - Accessible Building');
  await page.locator('#meter_role').selectOption('');
  await page.locator('#start_date').fill('2026-01-01');
}

async function openConsumptionReportParameters(page) {
  await loginAsAdmin(page);
  await page.goto('/consumption_report');

  await expect(page.getByRole('heading', { name: 'Consumption Report' })).toBeVisible();
  await page.getByRole('button', { name: /Report Parameters/ }).click();
  await expect(page.locator('#GenerateRAWReportModal')).toBeVisible();
}

async function fillConsumptionReportParameters(page) {
  const meterListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/generate_meter_list') && response.status() === 200
  );

  await page.locator('#site_id').fill('SITEA - Accessible Building');
  await page.locator('#site_id').dispatchEvent('change');
  await meterListResponsePromise;
  await expect(page.locator('#meter_list option[value="MTR-001"]')).toHaveCount(1);

  await page.locator('#meter_id').fill('MTR-001');
  await page.locator('#interval_type').selectOption('hourly');
  await page.locator('#chart_type').selectOption('bar');
  await page.locator('#start_date').fill('2026-01-01');
  await page.locator('#start_time').fill('00:00');
  await page.locator('#end_date').fill('2026-01-01');
  await page.locator('#end_time').fill('01:00');
}

async function openDemandReportParameters(page) {
  await loginAsAdmin(page);
  await page.goto('/demand_report');

  await expect(page.getByRole('heading', { name: 'Demand Report' })).toBeVisible();
  await page.getByRole('button', { name: /Report Parameters/ }).click();
  await expect(page.locator('#GenerateRAWReportModal')).toBeVisible();
}

async function fillDemandReportParameters(page) {
  const meterListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/generate_meter_list') && response.status() === 200
  );

  await page.locator('#site_id').fill('SITEA - Accessible Building');
  await page.locator('#site_id').dispatchEvent('change');
  await meterListResponsePromise;
  await expect(page.locator('#meter_list option[value="MTR-001"]')).toHaveCount(1);

  await page.locator('#meter_id').fill('MTR-001');
  await page.locator('#interval_type').selectOption('hourly');
  await page.locator('#chart_type').selectOption('bar');
  await page.locator('#start_date').fill('2026-01-01');
  await page.locator('#start_time').fill('00:00');
  await page.locator('#end_date').fill('2026-01-01');
  await page.locator('#end_time').fill('01:00');
}

async function openOfflineReportTabs(page) {
  await loginAsAdmin(page);

  const offlineGatewayResponsePromise = page.waitForResponse(response =>
    response.url().includes('/getOfflineGateway') && response.status() === 200
  );
  const offlineMeterResponsePromise = page.waitForResponse(response =>
    response.url().includes('/getOfflineMeter') && response.status() === 200
  );

  await page.goto(`/site_details/${seededSiteId}`);

  const offlineGatewayPayload = await (await offlineGatewayResponsePromise).json();
  const offlineMeterPayload = await (await offlineMeterResponsePromise).json();

  await expect(page.locator('button[title="List of Offline Gateway"]')).toBeVisible();
  await expect(page.locator('button[title="List of Offline Meter"]')).toBeVisible();
  await expect(page.locator('#offlinegatewaylist')).toBeVisible();

  await page.locator('button[title="List of Offline Meter"]').click();
  await expect(page.locator('#profile2')).toHaveClass(/active/);
  await expect(page.locator('#meterofflinelist')).toBeVisible();

  await page.locator('button[title="List of Offline Gateway"]').click();
  await expect(page.locator('#home2')).toHaveClass(/active/);

  return {
    offlineGatewayPayload,
    offlineMeterPayload,
  };
}

async function openSeededSiteDetailLists(page) {
  await loginAsAdmin(page);

  const locationListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/getMeterLocation') && response.status() === 200
  );
  const gatewayListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/getGatewayPerBLGandEEROOM') && response.status() === 200
  );
  const meterListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/getMeter') && response.status() === 200
  );

  await page.goto(`/site_details/${seededSiteId}`);

  return {
    locationListPayload: await (await locationListResponsePromise).json(),
    gatewayListPayload: await (await gatewayListResponsePromise).json(),
    meterListPayload: await (await meterListResponsePromise).json(),
  };
}

async function openUserMaintenance(page) {
  const userListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/user_list') && response.status() === 200
  );

  await loginAsAdmin(page);
  await page.goto('/user');
  const userListResponse = await userListResponsePromise;
  const userListPayload = await userListResponse.json();

  await expect(page.getByRole('heading', { name: 'User List' })).toBeVisible();
  await expect(page.locator('#userList')).toBeVisible();

  return userListPayload;
}

async function openDivisionMaintenance(page) {
  const divisionListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/division_list') && response.status() === 200
  );

  await loginAsAdmin(page);
  await page.goto('/division');
  const divisionListResponse = await divisionListResponsePromise;
  const divisionListPayload = await divisionListResponse.json();

  await expect(page.getByRole('heading', { name: 'Division List' })).toBeVisible();
  await expect(page.locator('#divisionList')).toBeVisible();

  return divisionListPayload;
}

async function openConfigurationFileMaintenance(page) {
  const configurationFileListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/configuration_file_list') && response.status() === 200
  );

  await loginAsAdmin(page);
  await page.goto('/configuration_file');
  const configurationFileListResponse = await configurationFileListResponsePromise;
  const configurationFileListPayload = await configurationFileListResponse.json();

  await expect(page.getByRole('heading', { name: 'Configuration File List' })).toBeVisible();
  await expect(page.locator('#configuration_fileList')).toBeVisible();

  return configurationFileListPayload;
}

async function openCompanyMaintenance(page) {
  const companyListResponsePromise = page.waitForResponse(response =>
    response.url().includes('/company_list') && response.status() === 200
  );

  await loginAsAdmin(page);
  await page.goto('/company');
  const companyListResponse = await companyListResponsePromise;
  const companyListPayload = await companyListResponse.json();

  await expect(page.getByRole('heading', { name: 'Company List' })).toBeVisible();
  await expect(page.locator('#companyList')).toBeVisible();

  return companyListPayload;
}

async function closeSuccessModal(page) {
  await page.locator('#SuccessModal button[data-bs-dismiss="modal"]').click();
  await expect(page.locator('#SuccessModal')).toBeHidden();
}

async function openAccountSettings(page) {
  const userInfoResponsePromise = page.waitForResponse(response =>
    response.url().includes('/user_info') && response.status() === 200
  );

  await page.locator('.nav-profile').click();
  await page.locator('#accountUser').click();
  const userInfoResponse = await userInfoResponsePromise;
  const userInfoPayload = await userInfoResponse.json();

  await expect(page.locator('#UserProfileModal')).toBeVisible();

  return userInfoPayload;
}

async function csrfTokenFromPage(page) {
  const pageHtml = await page.content();
  return pageHtml.match(/_token:\s*"([^"]+)"/)[1];
}

async function expectReportValidation(page, path, form, expectedErrors) {
  const response = await page.request.post(path, {
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': await csrfTokenFromPage(page),
      'X-Requested-With': 'XMLHttpRequest',
    },
    form,
  });
  const payload = await response.json();

  expect(response.status()).toBe(422);
  expect(payload.errors).toMatchObject(expectedErrors);
}

async function expectReportUiValidation(page, options) {
  await page.goto(options.path);
  await expect(page.getByRole('heading', { name: options.heading })).toBeVisible();
  await page.getByRole('button', { name: /Report Parameters/ }).click();
  await expect(page.locator(options.modal)).toBeVisible();

  for (const [selector, value] of Object.entries(options.values)) {
    await page.locator(selector).fill(value);
  }

  const validationResponsePromise = page.waitForResponse(response =>
    response.url().includes(options.endpoint) && response.status() === 422
  );

  await page.locator(options.submit).click();
  await validationResponsePromise;

  await expect(page.locator('#InvalidModal')).toBeVisible();
  for (const [selector, text] of Object.entries(options.errors)) {
    await expect(page.locator(selector)).toContainText(text);
  }

  await page.locator('#InvalidModalBtn').click();
  await expect(page.locator('#InvalidModal')).toBeHidden();
}

async function expectScopedReportBuildingOptions(page, path, headingName, modalSelector) {
  await page.goto(path);

  await expect(page.getByRole('heading', { name: headingName })).toBeVisible();
  await expect(page.locator('a[href$="/raw_report"]')).toContainText('Raw Data');
  await expect(page.locator('a[href$="/demand_report"]')).toContainText('KW Demand');
  await expect(page.locator('a[href$="/consumption_report"]')).toContainText('KWh Consumption');
  await expect(page.locator('a[href$="/site_report"]')).toContainText('Building');

  await page.getByRole('button', { name: /Report Parameters/ }).click();
  await expect(page.locator(modalSelector)).toBeVisible();

  const reportSites = await page.locator('#site_name option').evaluateAll(options =>
    options.map(option => ({
      value: option.getAttribute('value'),
      label: option.getAttribute('label'),
      siteId: option.getAttribute('data-id'),
      code: option.getAttribute('data-code'),
      description: option.getAttribute('data-description'),
    }))
  );

  expect(reportSites).toEqual([
    {
      value: 'SITEA - Accessible Building',
      label: 'SITEA - Accessible Building',
      siteId: seededSiteId,
      code: 'SITEA',
      description: 'Accessible Building',
    },
  ]);
  expect(reportSites.map(site => site.code)).not.toContain('SITEB');
}

async function expectAnonymousRedirectToLogin(page, path) {
  await page.goto(path);

  await expect(page).toHaveURL(/\/$/);
  await expect(page.getByText('You Have to Login First')).toBeVisible();
}

test.describe('legacy AMR browser characterization', () => {
  test('login page renders the legacy public entry point', async ({ page }) => {
    const errors = await expectNoPageErrors(page);

    await page.goto('/');

    await expect(page.getByText('Centralized Automated Meter Reading')).toBeVisible();
    await expect(page.locator('#user_name')).toBeVisible();
    await expect(page.locator('#InputPassword')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Login' })).toBeVisible();
    expect(errors).toEqual([]);
  });

  test('bad credentials keep the user on login with the legacy failure message', async ({ page }) => {
    await page.goto('/');
    await page.locator('#user_name').fill(adminUsername);
    await page.locator('#InputPassword').fill('wrong-password');
    await page.getByRole('button', { name: 'Login' }).click();

    await expect(page).toHaveURL(/\/$/);
    await expect(page.getByText('Incorrect Password')).toBeVisible();
  });

  test('password reset page renders the legacy public request form', async ({ page }) => {
    await page.goto('/passwordreset');

    await expect(page.getByText('Centralized Automated Meter Reading')).toBeVisible();
    await expect(page.getByText('Please Enter your Email Address Registered to your CAMR User Account')).toBeVisible();
    await expect(page.locator('#user_email_address')).toBeVisible();
    await expect(page.locator('#check-email')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Login' })).toHaveAttribute('href', '/');
  });

  test('password reset validates missing email without sending mail', async ({ page }) => {
    await page.goto('/passwordreset');
    const resetPageHtml = await page.content();
    const csrfToken = resetPageHtml.match(/_token:\s*"([^"]+)"/)[1];

    const resetResponse = await page.request.post('/reset-password', {
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      form: {
        user_email_address: '',
      },
    });
    const resetPayload = await resetResponse.json();

    expect(resetResponse.status()).toBe(422);
    expect(resetPayload.errors.user_email_address).toEqual(['Email Address is Required']);
  });

  test('password reset reports unknown email through the legacy success modal', async ({ page }) => {
    await page.goto('/passwordreset');
    await page.locator('#user_email_address').fill('missing-reset-user@example.test');

    const resetResponsePromise = page.waitForResponse(response =>
      response.url().includes('/reset-password') && response.status() === 200
    );

    await page.locator('#check-email').click();
    const resetResponse = await resetResponsePromise;
    const resetPayload = await resetResponse.json();

    expect(resetPayload).toEqual({
      success: 'Email Not Found!',
    });

    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Email Not Found!');
    await page.locator('#close_email_confirm').click();
    await expect(page).toHaveURL(/\/$/);
  });

  test('admin can login and see the seeded building list', async ({ page }) => {
    const siteListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/site/list') && response.status() === 200
    );

    await loginAsAdmin(page);
    const siteListResponse = await siteListResponsePromise;
    const siteListPayload = await siteListResponse.json();

    await expect(page.locator('#siteList')).toBeVisible();
    expect(siteListPayload.recordsTotal).toBe(2);
    expect(siteListPayload.data.map(row => row.building_code)).toEqual(['SITEA', 'SITEB']);
  });

  test('admin sees legacy validation messages when creating an empty building', async ({ page }) => {
    await loginAsAdmin(page);

    await page.locator('button[data-bs-target="#CreateSiteModal"]').click();
    await expect(page.locator('#CreateSiteModal')).toBeVisible();

    const createSiteResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_site_post') && response.status() === 422
    );

    await page.locator('#save-site').click();
    await createSiteResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#building_codeError')).toContainText('Building Code is Required');
    await expect(page.locator('#building_descriptionError')).toContainText('Building Description is Required');
    await expect(page.locator('#division_idError')).toContainText('Please Select a Division');
    await expect(page.locator('#company_idError')).toContainText('Please Select a Company');
  });

  test('admin can open the seeded site detail dashboard', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`/site_details/${seededSiteId}`);

    await expect(page).toHaveURL(new RegExp(`/site_details/${seededSiteId}$`));
    await expect(page.getByText('SITEA')).toBeVisible();
    await expect(page.locator('#sitegatewaylist-tab')).toBeVisible();
    await expect(page.locator('#sitemeterlist-tab')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Building Details' })).toBeVisible();
  });

  test('admin can use seeded site detail tabs and list contracts', async ({ page }) => {
    await loginAsAdmin(page);

    const locationListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/getMeterLocation') && response.status() === 200
    );
    const gatewayListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/getGatewayPerBLGandEEROOM') && response.status() === 200
    );
    const meterListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/getMeter') && response.status() === 200
    );

    await page.goto(`/site_details/${seededSiteId}`);

    const locationListPayload = await (await locationListResponsePromise).json();
    const gatewayListPayload = await (await gatewayListResponsePromise).json();
    const meterListPayload = await (await meterListResponsePromise).json();

    expect(locationListPayload.recordsTotal).toBe(1);
    expect(locationListPayload.data[0]).toMatchObject({
      location_code: 'ER-A',
      location_description: 'Electrical Room A',
    });

    expect(gatewayListPayload.recordsTotal).toBe(1);
    expect(gatewayListPayload.data[0]).toMatchObject({
      gateway_sn: 'GW-SN-001',
      gateway_mac: 'AA:BB:CC:DD:EE:01',
      gateway_ip: '10.0.0.10',
      location_code: 'ER-A',
      location_description: 'Electrical Room A',
    });

    expect(meterListPayload.recordsTotal).toBe(2);
    expect(meterListPayload.data.map(row => row.meter_name)).toEqual(['MTR-001', 'MTR-INACTIVE']);
    expect(meterListPayload.data[0]).toMatchObject({
      meter_name: 'MTR-001',
      customer_name: 'Tenant A',
      meter_status: 'Active',
      gateway_sn: 'GW-SN-001',
      location_code: 'ER-A',
      config_file: 'zmd402.cfg',
    });

    await page.locator('#sitemeterlocationlist-tab').click();
    await expect(page.locator('#bordered-sitemeterlocationlist')).toHaveClass(/active/);
    await expect(page.locator('#meterlocationlist')).toBeVisible();

    await page.locator('#sitegatewaylist-tab').click();
    await expect(page.locator('#bordered-sitegatewaylist')).toHaveClass(/active/);
    await expect(page.locator('#gatewaylist')).toBeVisible();

    await page.locator('#sitemeterlist-tab').click();
    await expect(page.locator('#bordered-sitemeterlist')).toHaveClass(/active/);
    await expect(page.locator('#meterlist')).toBeVisible();

    await page.locator('#sitemeterlocationlist-tab').click();
    await page.locator('button[data-bs-target="#CreateMeterLocationModal"]').click();
    await expect(page.locator('#CreateMeterLocationModal')).toBeVisible();

    const createLocationResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_meter_location_post') && response.status() === 422
    );

    await page.locator('#save-meterlocation').click();
    await createLocationResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#location_codeError')).toContainText('Location Code is Required');
    await expect(page.locator('#location_descriptionError')).toContainText('Location Description is Required');
  });

  test('admin can open the seeded location update modal without saving changes', async ({ page }) => {
    const { locationListPayload } = await openSeededSiteDetailLists(page);
    const seededLocation = locationListPayload.data.find(row => row.location_code === 'ER-A');

    expect(seededLocation).toBeTruthy();

    await page.locator('#sitemeterlocationlist-tab').click();
    await expect(page.locator('#meterlocationlist')).toBeVisible();

    const locationInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/meter_location_info') && response.status() === 200
    );

    await page.locator(`#meterlocationlist a#editMeterLocation[data-id="${seededLocation.location_id}"]`).click();
    const locationInfoResponse = await locationInfoResponsePromise;
    const locationInfoPayload = await locationInfoResponse.json();

    expect(locationInfoPayload[0]).toMatchObject({
      location_code: 'ER-A',
      location_description: 'Electrical Room A',
    });

    await expect(page.locator('#UpdateMeterLocationModal')).toBeVisible();
    await expect(page.locator('#update_location_code')).toHaveValue('ER-A');
    await expect(page.locator('#update_location_description')).toHaveValue('Electrical Room A');
    await expect(page.locator('#update-meterlocation')).toBeDisabled();
  });

  test('admin can create, reject duplicate, update, and delete a disposable site location', async ({ page }) => {
    const uniqueSuffix = Date.now().toString().slice(-8);
    const createdLocationCode = `L${uniqueSuffix}`;
    const createdLocationDescription = `Browser Disposable Location ${uniqueSuffix}`;
    const updatedLocationCode = `U${uniqueSuffix}`;
    const updatedLocationDescription = `${createdLocationDescription} Updated`;

    await openSeededSiteDetailLists(page);
    await page.locator('#sitemeterlocationlist-tab').click();
    await expect(page.locator('#meterlocationlist')).toBeVisible();

    await page.locator('button[data-bs-target="#CreateMeterLocationModal"]').click();
    await expect(page.locator('#CreateMeterLocationModal')).toBeVisible();
    await page.locator('#location_code').fill(createdLocationCode);
    await page.locator('#location_description').fill(createdLocationDescription);

    const createLocationResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_meter_location_post') && response.status() === 200
    );
    const createdAccordionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/get_ee_room_location_accordion') && response.status() === 200
    );
    const createdLocationListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/getMeterLocation') && response.status() === 200
    );

    await page.locator('#save-meterlocation').click();
    const createLocationResponse = await createLocationResponsePromise;
    const createLocationPayload = await createLocationResponse.json();
    await createdAccordionResponsePromise;
    const createdLocationListPayload = await (await createdLocationListResponsePromise).json();
    const createdLocation = createdLocationListPayload.data.find(row =>
      row.location_code === createdLocationCode && row.location_description === createdLocationDescription
    );

    expect(createLocationPayload).toEqual({
      success: 'Meter Location Information Successfully Created!',
    });
    expect(createdLocation).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Meter Location Information Successfully Created!');
    await closeSuccessModal(page);
    await expect(page.locator('#meterlocationlist')).toContainText(createdLocationDescription);

    await page.locator('#location_code').fill(createdLocationCode);
    await page.locator('#location_description').fill(createdLocationDescription);
    const duplicateLocationResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_meter_location_post') && response.status() === 422
    );

    await page.locator('#save-meterlocation').click();
    await duplicateLocationResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#location_codeError')).toContainText(`${createdLocationCode} has already been taken.`);
    await expect(page.locator('#location_descriptionError')).toContainText(`${createdLocationDescription} has already been taken.`);
    await page.locator('#InvalidModalBtn').click();
    await expect(page.locator('#InvalidModal')).toBeHidden();

    await page.locator('#CreateMeterLocationModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#CreateMeterLocationModal')).toBeHidden();

    const locationInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/meter_location_info') && response.status() === 200
    );

    await page.locator(`#meterlocationlist a#editMeterLocation[data-id="${createdLocation.location_id}"]`).click();
    const locationInfoResponse = await locationInfoResponsePromise;
    const locationInfoPayload = await locationInfoResponse.json();

    expect(locationInfoPayload[0]).toMatchObject({
      location_code: createdLocationCode,
      location_description: createdLocationDescription,
    });
    await expect(page.locator('#UpdateMeterLocationModal')).toBeVisible();
    await expect(page.locator('#update-meterlocation')).toBeDisabled();

    const changedLocationInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/meter_location_info') && response.status() === 200
    );
    await page.locator('#update_location_code').fill(updatedLocationCode);
    await page.locator('#update_location_code').dispatchEvent('change');
    await changedLocationInfoResponsePromise;
    await page.locator('#update_location_description').fill(updatedLocationDescription);
    await page.locator('#update_location_description').dispatchEvent('change');
    await expect(page.locator('#update-meterlocation')).toBeEnabled();

    const updateLocationResponsePromise = page.waitForResponse(response =>
      response.url().includes('/update_meter_location_post') && response.status() === 200
    );
    const updatedAccordionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/get_ee_room_location_accordion') && response.status() === 200
    );
    const updatedLocationListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/getMeterLocation') && response.status() === 200
    );

    await page.locator('#update-meterlocation').click();
    const updateLocationResponse = await updateLocationResponsePromise;
    const updateLocationPayload = await updateLocationResponse.json();
    await updatedAccordionResponsePromise;
    const updatedLocationListPayload = await (await updatedLocationListResponsePromise).json();
    const updatedLocation = updatedLocationListPayload.data.find(row =>
      row.location_code === updatedLocationCode && row.location_description === updatedLocationDescription
    );

    expect(updateLocationPayload).toEqual({
      success: 'Building Information Successfully Updated!',
    });
    expect(updatedLocation).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Building Information Successfully Updated!');
    await closeSuccessModal(page);
    await page.locator('#UpdateMeterLocationModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#UpdateMeterLocationModal')).toBeHidden();
    await expect(page.locator('#meterlocationlist')).toContainText(updatedLocationDescription);

    const deleteInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/meter_location_info') && response.status() === 200
    );

    await page.locator(`#meterlocationlist a#deleteMeterLocation[data-id="${updatedLocation.location_id}"]`).click();
    await deleteInfoResponsePromise;

    await expect(page.locator('#meterlocationDeleteModal')).toBeVisible();
    await expect(page.locator('#meter_location_code_delete')).toContainText(updatedLocationCode);
    await expect(page.locator('#meter_location_description_delete')).toContainText(updatedLocationDescription);

    const deleteLocationResponsePromise = page.waitForResponse(response =>
      response.url().includes('/delete_meter_location_confirmed') && response.status() === 200
    );
    const deletedAccordionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/get_ee_room_location_accordion') && response.status() === 200
    );
    const deletedLocationListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/getMeterLocation') && response.status() === 200
    );

    await page.locator('#deletemeterlocationConfirmed').click();
    const deleteLocationResponse = await deleteLocationResponsePromise;
    await deletedAccordionResponsePromise;
    const deletedLocationListPayload = await (await deletedLocationListResponsePromise).json();

    expect(await deleteLocationResponse.text()).toBe('Deleted');
    expect(deletedLocationListPayload.data.map(row => row.location_description)).not.toContain(updatedLocationDescription);
    await expect(page.locator('#meterlocationDeleteModalConfirmed')).toBeVisible();
    await expect(page.locator('#meter_location_code_delete_confirmed')).toContainText(updatedLocationCode);
    await expect(page.locator('#meter_location_description_delete_confirmed')).toContainText(updatedLocationDescription);
    await page.locator('#meterlocationDeleteModalConfirmed button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#meterlocationDeleteModalConfirmed')).toBeHidden();
    await expect(page.locator('#meterlocationlist')).not.toContainText(updatedLocationDescription);
  });

  test('admin can open the seeded gateway update modal without saving changes', async ({ page }) => {
    const { gatewayListPayload } = await openSeededSiteDetailLists(page);
    const seededGateway = gatewayListPayload.data.find(row => row.gateway_sn === 'GW-SN-001');

    expect(seededGateway).toBeTruthy();

    await page.locator('#sitegatewaylist-tab').click();
    await expect(page.locator('#gatewaylist')).toBeVisible();

    const gatewayInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/gateway_info') && response.status() === 200
    );

    await page.locator(`#gatewaylist a#EditGateway[data-id="${seededGateway.rtu_id}"]`).click();
    const gatewayInfoResponse = await gatewayInfoResponsePromise;
    const gatewayInfoPayload = await gatewayInfoResponse.json();

    expect(gatewayInfoPayload).toMatchObject({
      gateway_sn: 'GW-SN-001',
      gateway_mac: 'AA:BB:CC:DD:EE:01',
      gateway_ip: '10.0.0.10',
      connection_type: 'LAN',
      gateway_description: 'Fixture gateway',
      location_code: 'ER-A',
      location_description: 'Electrical Room A',
    });

    await expect(page.locator('#UpdateGatewayModal')).toBeVisible();
    await expect(page.locator('#update_gateway_sn')).toHaveValue('GW-SN-001');
    await expect(page.locator('#update_gateway_mac')).toHaveValue('AA:BB:CC:DD:EE:01');
    await expect(page.locator('#update_gateway_ip')).toHaveValue('10.0.0.10');
    await expect(page.locator('#update_connection_type')).toHaveValue('LAN');
    await expect(page.locator('#update_gateway_location')).toHaveValue('ER-A - Electrical Room A');
    await expect(page.locator('#update_gateway_description')).toHaveValue('Fixture gateway');
    await expect(page.locator('#update-gateway')).toBeDisabled();
  });

  test('admin sees legacy validation messages when creating an empty gateway', async ({ page }) => {
    await openSeededSiteDetailLists(page);
    await page.locator('#sitegatewaylist-tab').click();
    await expect(page.locator('#gatewaylist')).toBeVisible();

    await page.locator('button[data-bs-target="#CreateGatewayModal"]').click();
    await expect(page.locator('#CreateGatewayModal')).toBeVisible();

    const createGatewayResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_gateway_post') && response.status() === 422
    );

    await page.locator('#save-gateway').click();
    await createGatewayResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#gateway_snError')).toContainText('Gateway Serial Number is Required');
    await expect(page.locator('#gateway_macError')).toContainText('MAC Address is Required');
    await expect(page.locator('#gateway_ipError')).toContainText('IP Address/Sim # is Required');
    await expect(page.locator('#gateway_location_meterError')).toContainText('Please Select Area/EE Room');
  });

  test('admin can open the seeded meter update modal without saving changes', async ({ page }) => {
    const { meterListPayload } = await openSeededSiteDetailLists(page);
    const seededMeter = meterListPayload.data.find(row => row.meter_name === 'MTR-001');

    expect(seededMeter).toBeTruthy();

    await page.locator('#sitemeterlist-tab').click();
    await expect(page.locator('#meterlist')).toBeVisible();

    const meterInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/meter_info') && response.status() === 200
    );

    await page.locator(`#meterlist a#EditMeter[data-id="${seededMeter.meter_id}"]`).click();
    const meterInfoResponse = await meterInfoResponsePromise;
    const meterInfoPayload = await meterInfoResponse.json();

    expect(meterInfoPayload[0]).toMatchObject({
      meter_name: 'MTR-001',
      customer_name: 'Tenant A',
      meter_role: 'Client Meter',
      config_file: 'zmd402.cfg',
      meter_default_name: '1',
      meter_type: 'Power',
      meter_brand: 'FixtureBrand',
      meter_multiplier: 1,
      gateway_sn: 'GW-SN-001',
      location_code: 'ER-A',
      location_description: 'Electrical Room A',
      meter_status: 'Active',
    });

    await expect(page.locator('#UpdateMeterModal')).toBeVisible();
    await expect(page.locator('#update_meter_name')).toHaveValue('MTR-001');
    await expect(page.locator('#update_customer_name')).toHaveValue('Tenant A');
    await expect(page.locator('#update_meter_model_id')).toHaveValue('zmd402.cfg');
    await expect(page.locator('#update_meter_name_addressable')).not.toBeChecked();
    await expect(page.locator('#update_meter_default_name')).toBeDisabled();
    await expect(page.locator('#update_meter_default_name')).toHaveValue('');
    await expect(page.locator('#update_meter_type')).toHaveValue('Power');
    await expect(page.locator('#update_meter_brand')).toHaveValue('FixtureBrand');
    await expect(page.locator('#update_meter_multiplier')).toHaveValue('1');
    await expect(page.locator('#update_rtu_sn_number_id')).toHaveValue('GW-SN-001');
    await expect(page.locator('#update_location_id')).toHaveValue('ER-A - Electrical Room A');
    await expect(page.locator('#update_meter_status')).toHaveValue('ACTIVE');
    await expect(page.locator('#update_meter_remarks')).toHaveValue('');
    await expect(page.locator('#update-meter')).toBeDisabled();
  });

  test('admin sees legacy validation messages when creating an empty meter', async ({ page }) => {
    await openSeededSiteDetailLists(page);
    await page.locator('#sitemeterlist-tab').click();
    await expect(page.locator('#meterlist')).toBeVisible();

    await page.locator('button[data-bs-target="#CreateMeterModal"]').click();
    await expect(page.locator('#CreateMeterModal')).toBeVisible();

    const createMeterResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_meter_post') && response.status() === 422
    );

    await page.locator('#save-meter').click();
    await createMeterResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#meter_descriptionError')).toContainText('Meter Description/Serial Number is Required');
    await expect(page.locator('#meter_model_idError')).toContainText('Please Select Meter Configuration File');
    await expect(page.locator('#rtu_sn_number_idError')).toContainText('Please Select Gateway Serial Number');
    await expect(page.locator('#location_meterError')).toContainText('Please Select Area/EE Room');
  });

  test('admin can view and download offline gateway and meter reports', async ({ page }) => {
    const { offlineGatewayPayload, offlineMeterPayload } = await openOfflineReportTabs(page);

    expect(offlineGatewayPayload.recordsTotal).toBe(1);
    expect(offlineGatewayPayload.data[0]).toMatchObject({
      gateway_sn: 'GW-SN-001',
      gateway_mac: 'AA:BB:CC:DD:EE:01',
      gateway_ip: '10.0.0.10',
      location_code: 'ER-A',
      location_description: 'Electrical Room A',
    });
    expect(offlineGatewayPayload.data[0].status).toContain('0000-00-00 00:00:00');

    expect(offlineMeterPayload.recordsTotal).toBe(2);
    expect(offlineMeterPayload.data.map(row => row.meter_name)).toEqual(['MTR-001', 'MTR-INACTIVE']);
    expect(offlineMeterPayload.data[0]).toMatchObject({
      meter_name: 'MTR-001',
      customer_name: 'Tenant A',
      meter_status: 'Active',
      gateway_sn: 'GW-SN-001',
      location_code: 'ER-A',
      location_description: 'Electrical Room A',
      meter_role: 'Client Meter',
      config_file: 'zmd402.cfg',
      meter_default_name: '1',
    });

    const gatewayDownloadResponse = await page.request.get(`/download_offline_gateway?siteID=${seededSiteId}`);
    expect(gatewayDownloadResponse.status()).toBe(200);
    expect(gatewayDownloadResponse.headers()['content-type']).toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect(gatewayDownloadResponse.headers()['content-disposition']).toContain('Offline_Gateway_SITEA_');
    expect(gatewayDownloadResponse.headers()['content-disposition']).toContain('.xlsx');
    expectWorkbookText(await readXlsxText(gatewayDownloadResponse, 'offline-gateway.xlsx'), [
      'GW-SN-001',
      'AA:BB:CC:DD:EE:01',
      '10.0.0.10',
      'ER-A',
      'Electrical Room A',
    ]);

    const meterDownloadResponse = await page.request.get(`/download_offline_meter?siteID=${seededSiteId}`);
    expect(meterDownloadResponse.status()).toBe(200);
    expect(meterDownloadResponse.headers()['content-type']).toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect(meterDownloadResponse.headers()['content-disposition']).toContain('Offline_Meter_SITEA_');
    expect(meterDownloadResponse.headers()['content-disposition']).toContain('.xlsx');
    expectWorkbookText(await readXlsxText(meterDownloadResponse, 'offline-meter.xlsx'), [
      'MTR-001',
      'Tenant A',
      'GW-SN-001',
      'ER-A',
      'zmd402.cfg',
    ]);
  });

  test('admin can trigger the seeded site as-built export', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`/site_details/${seededSiteId}`);

    await expect(page.getByRole('heading', { name: 'Building Details' })).toBeVisible();
    await expect(page.getByText('Building Code: SITEA')).toBeVisible();
    await expect(page.locator('a[title="Download Meter and Gateway List"]')).toBeVisible();

    const popupPromise = page.waitForEvent('popup');
    await page.locator('a[title="Download Meter and Gateway List"]').click();
    const popup = await popupPromise;
    expect(popup).toBeTruthy();
    await popup.close();

    const asBuiltResponse = await page.request.get(`/generate_site_as_built_excel?siteID=${seededSiteId}`);
    expect(asBuiltResponse.status()).toBe(200);
    expect(asBuiltResponse.headers()['content-type']).toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect(asBuiltResponse.headers()['content-disposition']).toContain('Accessible%20Building_SITEA_Building%20Meters%20and%20Gateway%20List_');
    expect(asBuiltResponse.headers()['content-disposition']).toContain('.xlsx');
    expectWorkbookText(await readXlsxText(asBuiltResponse, 'site-as-built.xlsx'), [
      'SITEA',
      'Accessible Building',
      'MTR-001',
      'GW-SN-001',
      'Tenant A',
      'zmd402.cfg',
    ]);
  });

  test('anonymous users are redirected away from protected pages', async ({ page }) => {
    await page.goto('/site');

    await expect(page).toHaveURL(/\/$/);
    await expect(page.getByText('You Have to Login First')).toBeVisible();
  });

  test('anonymous users are redirected from representative protected direct URLs', async ({ page }) => {
    await expectAnonymousRedirectToLogin(page, '/site');
    await expectAnonymousRedirectToLogin(page, `/site_details/${seededSiteId}`);
    await expectAnonymousRedirectToLogin(page, '/raw_report');
    await expectAnonymousRedirectToLogin(page, '/site_report');
    await expectAnonymousRedirectToLogin(page, '/consumption_report');
    await expectAnonymousRedirectToLogin(page, '/demand_report');
    await expectAnonymousRedirectToLogin(page, '/user');
    await expectAnonymousRedirectToLogin(page, '/division');
    await expectAnonymousRedirectToLogin(page, '/company');
    await expectAnonymousRedirectToLogin(page, '/configuration_file');
  });

  test('admin can import meters from the legacy gateway CSV modal', async ({ page }) => {
    await openGatewayUploadModal(page);

    const missingFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/import_meters') && response.status() === 422
    );
    await page.locator('#import').click();
    await missingFileResponsePromise;
    await expect(page.locator('#csv_fileError')).toContainText('Please check the Encoded Data on the Upload File.');
    await page.locator('#InvalidModalBtn').click();
    await expect(page.locator('#InvalidModal')).toBeHidden();

    await page.locator('#csv_file').setInputFiles({
      name: 'meters.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from('ER-A,MTR-BAD\n'),
    });

    const badFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/import_meters') && response.status() === 200
    );
    await page.locator('#import').click();
    const badFileResponse = await badFileResponsePromise;
    const badFilePayload = await badFileResponse.json();

    expect(badFilePayload).toMatchObject({
      error: 'CSV File Error, please check the Content/Column Count.',
      total_line: 0,
      result_csv_import: 0,
    });
    await expect(page.locator('#csv_fileError')).toContainText('CSV File Error, please check the Content/Column Count.');
    await page.locator('#InvalidModalBtn').click();
    await expect(page.locator('#InvalidModal')).toBeHidden();

    await page.locator('#csv_file').setInputFiles({
      name: 'meters.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from('ER-A,MTR-BROWSER,Tenant Browser,Brand X,Power,Active,zmd402.cfg,5,Client Meter,1,Browser import\n'),
    });

    const validFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/import_meters') && response.status() === 200
    );
    await page.locator('#import').click();
    const validFileResponse = await validFileResponsePromise;
    const validFilePayload = await validFileResponse.json();

    expect(validFilePayload.success).toBe('CSV File Successfully Imported!');
    expect(validFilePayload.total_line).toBe(1);
    expect(validFilePayload.result_csv_import[0]).toMatchObject({
      meter_name: 'MTR-BROWSER',
      tenant_name: 'Tenant Browser',
      import_mode: 'New Meter',
    });

    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#meterlistLoadPerGateway_upload')).toContainText('MTR-BROWSER');
  });

  test('admin can generate the raw data report for seeded meter readings', async ({ page }) => {
    await openRawReportParameters(page);
    await fillRawReportParameters(page);

    const rawReportResponsePromise = page.waitForResponse(response =>
      response.url().includes('/generate_raw_report') && response.status() === 200
    );

    await page.locator('#generate_raw_report').click();
    const rawReportResponse = await rawReportResponsePromise;
    const rawReportPayload = await rawReportResponse.json();

    expect(rawReportPayload.recordsTotal).toBe(2);
    expect(rawReportPayload.data.map(row => row.datetime)).toEqual([
      '2026-01-01 00:00:00',
      '2026-01-01 01:00:00',
    ]);

    await expect(page.locator('#GenerateRAWReportModal')).toBeHidden();
    await expect(page.locator('#meter_description')).toContainText('MTR-001');
    await expect(page.locator('#tenant_name')).toContainText('Tenant A');
    await expect(page.locator('#building_code')).toContainText('SITEA');
    await expect(page.locator('#date_start_txt')).toContainText('2026-01-01 00:00');
    await expect(page.locator('#date_end_txt')).toContainText('2026-01-01 01:00');
    await expect(page.locator('#raw_report_html_table')).toContainText('2026-01-01 00:00:00');
    await expect(page.locator('#raw_report_html_table')).toContainText('2026-01-01 01:00:00');
    await expect(page.getByRole('button', { name: /Excel/ })).toBeVisible();

    const popupPromise = page.waitForEvent('popup');
    await page.getByRole('button', { name: /Excel/ }).click();
    const popup = await popupPromise;
    expect(popup).toBeTruthy();
    await popup.close();
  });

  test('report generation endpoints return legacy missing-parameter validation messages', async ({ page }) => {
    await loginAsAdmin(page);

    await page.goto('/raw_report');
    await expectReportValidation(page, '/generate_raw_report', {
      site_id: '',
      meter_id: '',
      start_date: '',
      start_time: '',
      end_date: '',
      end_time: '',
    }, {
      site_id: ['Please select a Building'],
      meter_id: ['Please select a Meter'],
      start_date: ['Please select a Start Date'],
      start_time: ['Please select a Start Time'],
      end_date: ['Please select a End Date'],
      end_time: ['Please select a End Time'],
    });

    await page.goto('/site_report');
    await expectReportValidation(page, '/generate_site_report', {
      site_id: '',
    }, {
      site_id: ['Please select a Building'],
    });

    await page.goto('/sap_report');
    await expectReportValidation(page, '/generate_sap_report', {
      site_id: '',
      start_date: '',
      end_date: '',
    }, {
      site_id: ['Please select a Building'],
      start_date: ['Please select a Start Date'],
      end_date: ['Please select a End Date'],
    });

    await page.goto('/consumption_report');
    await expectReportValidation(page, '/generate_consumption_report/hourly', {
      site_id: '',
      meter_id: '',
      start_date: '',
      start_time: '',
      end_date: '',
      end_time: '',
    }, {
      site_id: ['Please select a Building'],
      meter_id: ['Please select a Meter'],
      start_date: ['Please select a Start Date'],
      start_time: ['Please select a Start Time'],
      end_date: ['Please select a End Date'],
      end_time: ['Please select a End Time'],
    });

    await page.goto('/demand_report');
    await expectReportValidation(page, '/generate_demand_report/hourly', {
      site_id: '',
      meter_id: '',
      start_date: '',
      start_time: '',
      end_date: '',
      end_time: '',
    }, {
      site_id: ['Please select a Building'],
      meter_id: ['Please select a Meter'],
      start_date: ['Please select a Start Date'],
      start_time: ['Please select a Start Time'],
      end_date: ['Please select a End Date'],
      end_time: ['Please select a End Time'],
    });
  });

  test('report parameter modals render stable missing-parameter validation messages', async ({ page }) => {
    await loginAsAdmin(page);

    await expectReportUiValidation(page, {
      path: '/raw_report',
      heading: 'RAW Report',
      modal: '#GenerateRAWReportModal',
      endpoint: '/generate_raw_report',
      submit: '#generate_raw_report',
      values: {
        '#site_id': '',
        '#meter_id': '',
        '#start_date': '',
        '#end_date': '',
      },
      errors: {
        '#site_idError': 'Please select a Building',
        '#meter_idError': 'Please select a Meter',
        '#start_dateError': 'Please select a Start Date',
        '#end_dateError': 'Please select a End Date',
      },
    });

    await expectReportUiValidation(page, {
      path: '/site_report',
      heading: 'Building Report',
      modal: '#GenerateSAPReportModal',
      endpoint: '/generate_site_report',
      submit: '#generate_sap_report',
      values: {
        '#site_id': '',
      },
      errors: {
        '#site_idError': 'Please select a Building',
      },
    });

    await expectReportUiValidation(page, {
      path: '/sap_report',
      heading: 'SAP Report',
      modal: '#GenerateSAPReportModal',
      endpoint: '/generate_sap_report',
      submit: '#generate_sap_report',
      values: {
        '#site_id': '',
      },
      errors: {
        '#site_idError': 'Please select a Building',
      },
    });

    await expectReportUiValidation(page, {
      path: '/consumption_report',
      heading: 'Consumption Report',
      modal: '#GenerateRAWReportModal',
      endpoint: '/generate_consumption_report/hourly',
      submit: '#generate_raw_report',
      values: {
        '#site_id': '',
        '#meter_id': '',
        '#start_date': '',
        '#end_date': '',
      },
      errors: {
        '#site_idError': 'Please select a Building',
        '#meter_idError': 'Please select a Meter',
        '#start_dateError': 'Please select a Start Date',
        '#end_dateError': 'Please select a End Date',
      },
    });

    await expectReportUiValidation(page, {
      path: '/demand_report',
      heading: 'Demand Report',
      modal: '#GenerateRAWReportModal',
      endpoint: '/generate_demand_report/',
      submit: '#generate_raw_report',
      values: {
        '#site_id': '',
        '#meter_id': '',
        '#start_date': '',
        '#end_date': '',
      },
      errors: {
        '#site_idError': 'Please select a Building',
        '#meter_idError': 'Please select a Meter',
        '#start_dateError': 'Please select a Start Date',
        '#end_dateError': 'Please select a End Date',
      },
    });
  });

  test('scoped users only receive assigned buildings in the site list contract', async ({ page }) => {
    const siteListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/site/user/list') && response.status() === 200
    );

    await loginAsScopedUser(page);
    const siteListResponse = await siteListResponsePromise;
    const siteListPayload = await siteListResponse.json();

    await expect(page.locator('#siteList')).toBeVisible();
    await expect(page.locator('button[data-bs-target="#CreateSiteModal"]')).toHaveCount(0);
    await expect(page.locator('a[href$="/user"]')).toHaveCount(0);

    expect(siteListPayload.recordsTotal).toBe(1);
    expect(siteListPayload.data.map(row => row.building_code)).toEqual(['SITEA']);
    expect(siteListPayload.data.map(row => row.building_description)).toEqual(['Accessible Building']);
    expect(siteListPayload.data[0].action).toContain(`/site_details/${seededSiteId}`);
    expect(siteListPayload.data[0].action).not.toContain('id="editSite"');
    expect(siteListPayload.data[0].action).not.toContain('id="deleteSite"');
  });

  test('scoped users can still open an unassigned site detail URL directly', async ({ page }) => {
    await loginAsScopedUser(page);
    await page.goto(`/site_details/${restrictedSiteId}`);

    await expect(page).toHaveURL(new RegExp(`/site_details/${restrictedSiteId}$`));
    await expect(page.getByRole('heading', { name: 'Building Details' })).toBeVisible();
    await expect(page.getByText('SITEB')).toBeVisible();
    await expect(page.getByText('Building Description: Restricted Building')).toBeVisible();
    await expect(page.locator('#sitegatewaylist-tab')).toBeVisible();
    await expect(page.locator('#sitemeterlist-tab')).toBeVisible();
  });

  test('scoped users keep report menu access but report building choices are assigned only', async ({ page }) => {
    await loginAsScopedUser(page);

    await expectScopedReportBuildingOptions(page, '/raw_report', 'RAW Report', '#GenerateRAWReportModal');
    await expectScopedReportBuildingOptions(page, '/site_report', 'Building Report', '#GenerateSAPReportModal');
    await expectScopedReportBuildingOptions(page, '/consumption_report', 'Consumption Report', '#GenerateRAWReportModal');
    await expectScopedReportBuildingOptions(page, '/demand_report', 'Demand Report', '#GenerateRAWReportModal');
  });

  test('scoped users can directly open legacy admin maintenance URLs', async ({ page }) => {
    await loginAsScopedUser(page);

    await page.goto('/user');
    await expect(page).toHaveURL(/\/user$/);
    await expect(page.getByRole('heading', { name: 'User List' })).toBeVisible();
    await expect(page.locator('#userList')).toBeVisible();

    await page.goto('/division');
    await expect(page).toHaveURL(/\/division$/);
    await expect(page.getByRole('heading', { name: 'Division List' })).toBeVisible();
    await expect(page.locator('#divisionList')).toBeVisible();

    await page.goto('/company');
    await expect(page).toHaveURL(/\/company$/);
    await expect(page.getByRole('heading', { name: 'Company List' })).toBeVisible();
    await expect(page.locator('#companyList')).toBeVisible();
  });

  test('scoped users can directly reach configuration file maintenance routes', async ({ page }) => {
    await loginAsScopedUser(page);

    const configurationFileListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_list') && response.status() === 200
    );

    await page.goto('/configuration_file');
    const configurationFileListPayload = await (await configurationFileListResponsePromise).json();
    const seededConfigurationFile = configurationFileListPayload.data.find(row => row.config_file === 'zmd402.cfg');

    expect(seededConfigurationFile).toBeTruthy();
    await expect(page).toHaveURL(/\/configuration_file$/);
    await expect(page.getByRole('heading', { name: 'Configuration File List' })).toBeVisible();
    await expect(page.locator('#configuration_fileList')).toBeVisible();
    await expect(page.locator('button[data-bs-target="#Createconfiguration_fileModal"]')).toBeVisible();
    expect(seededConfigurationFile.action).toContain('editconfiguration_file');
    expect(seededConfigurationFile.action).toContain('deleteconfiguration_file');

    const csrfToken = await csrfTokenFromPage(page);
    const configurationFileInfoResponse = await page.request.post('/configuration_file_info', {
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      form: {
        ConfigFileID: seededConfigurationFile.config_id,
      },
    });
    const configurationFileInfoPayload = await configurationFileInfoResponse.json();

    expect(configurationFileInfoResponse.status()).toBe(200);
    expect(configurationFileInfoPayload).toMatchObject({
      config_file: 'zmd402.cfg',
    });

    const createConfigurationFileResponse = await page.request.post('/create_configuration_file_post', {
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      form: {
        configuration_file_name: '',
      },
    });
    const createConfigurationFilePayload = await createConfigurationFileResponse.json();

    expect(createConfigurationFileResponse.status()).toBe(422);
    expect(createConfigurationFilePayload.errors).toMatchObject({
      configuration_file_name: ['File Name is Required'],
    });
  });

  test('admin can generate the building site report for seeded meter consumption', async ({ page }) => {
    await openSiteReportParameters(page);
    await fillSiteReportParameters(page);

    const siteReportResponsePromise = page.waitForResponse(response =>
      response.url().includes('/generate_site_report') && response.status() === 200
    );

    await page.locator('#generate_sap_report').click();
    const siteReportResponse = await siteReportResponsePromise;
    const siteReportPayload = await siteReportResponse.json();

    expect(siteReportPayload.recordsTotal).toBe(1);
    expect(siteReportPayload.data[0]).toMatchObject({
      meter_name: 'MTR-001',
      customer_name: 'Tenant A',
      gateway_sn: 'GW-SN-001',
      location_code: 'ER-A',
      start_reading: 100,
      start_reading_datetime: '2026-01-01 00:00:00',
      ending_reading: 125,
      ending_reading_datetime: '2026-01-01 01:00:00',
      current_consumption: 25,
    });

    await expect(page.locator('#GenerateSAPReportModal')).toBeHidden();
    await expect(page.locator('#building_code')).toContainText('SITEA');
    await expect(page.locator('#building_name')).toContainText('Accessible Building');
    await expect(page.locator('#date_start_txt')).toContainText('2025-12-31 23:59');
    await expect(page.locator('#date_end_txt')).toContainText('2026-01-01 01:01');
    await expect(page.locator('#total_current_consumption_top')).toContainText('25');
    await expect(page.locator('#site_report_html_table')).toContainText('Tenant A');
    await expect(page.locator('#site_report_html_table')).toContainText('MTR-001');
    await expect(page.locator('#site_report_html_table')).toContainText('GW-SN-001');
    await expect(page.getByRole('button', { name: /Excel/ })).toBeVisible();

    const popupPromise = page.waitForEvent('popup');
    await page.getByRole('button', { name: /Excel/ }).click();
    const popup = await popupPromise;
    expect(popup).toBeTruthy();
    await popup.close();
  });

  test('admin can generate the SAP report for seeded meter readings', async ({ page }) => {
    await openSapReportParameters(page);
    await fillSapReportParameters(page);

    const sapReportResponsePromise = page.waitForResponse(response =>
      response.url().includes('/generate_sap_report') && response.status() === 200
    );

    await page.locator('#generate_sap_report').click();
    const sapReportResponse = await sapReportResponsePromise;
    const sapReportPayload = await sapReportResponse.json();

    expect(sapReportPayload.recordsTotal).toBe(1);
    expect(sapReportPayload.data[0]).toMatchObject({
      meter_name: 'MTR-001',
      date_generated: '01/01/2026',
      time_generated: '01:00:00',
      customer_name: 'Tenant A',
      meter_multiplier: 1,
      meter_type: 'Power',
      building_code: 'SITEA',
      current_reading: 125,
      current_reading_datetime: '2026-01-01 01:00:00',
      current_consumption: '125.000',
      previous_consumption: '0.000',
      difference_consumption: '0.00 %',
    });

    await expect(page.locator('#GenerateSAPReportModal')).toBeHidden();
    await expect(page.locator('#building_code')).toContainText('SITEA');
    await expect(page.locator('#building_name')).toContainText('Accessible Building');
    await expect(page.locator('#cut_off')).toContainText('2026-01-01');
    await expect(page.locator('#sap_report_html_table')).toContainText('MTR-001');
    await expect(page.locator('#sap_report_html_table')).toContainText('Tenant A');
    await expect(page.locator('#sap_report_html_table')).toContainText('125.000');
    await expect(page.getByRole('button', { name: /Excel/ })).toBeVisible();

    const popupPromise = page.waitForEvent('popup');
    await page.getByRole('button', { name: /Excel/ }).click();
    const popup = await popupPromise;
    expect(popup).toBeTruthy();
    await popup.close();
  });

  test('admin can generate the hourly consumption report for seeded meter readings', async ({ page }) => {
    await openConsumptionReportParameters(page);
    await fillConsumptionReportParameters(page);

    const consumptionReportResponsePromise = page.waitForResponse(response =>
      response.url().includes('/generate_consumption_report/hourly') && response.status() === 200
    );

    await page.locator('#generate_raw_report').click();
    const consumptionReportResponse = await consumptionReportResponsePromise;
    const consumptionReportPayload = await consumptionReportResponse.json();

    expect(consumptionReportPayload.recordsTotal).toBe(1);
    expect(consumptionReportPayload.data[0]).toMatchObject({
      hour: '2026-01-01 00:00:00',
      building_code: 'SITEA',
      min_datetime: '2026-01-01 00:00:00',
      min_wh_total: 100,
      max_datetime: '2026-01-01 01:00:00',
      max_wh_total: 125,
      multiplier: 1,
      kwh_total: 25,
    });

    await expect(page.locator('#GenerateRAWReportModal')).toBeHidden();
    await expect(page.locator('#meter_description')).toContainText('MTR-001');
    await expect(page.locator('#tenant_name')).toContainText('Tenant A');
    await expect(page.locator('#gateway_description')).toContainText('GW-SN-001');
    await expect(page.locator('#building_code')).toContainText('SITEA');
    await expect(page.locator('#building_name')).toContainText('Accessible Building');
    await expect(page.locator('#date_start_txt')).toContainText('2026-01-01 00:00');
    await expect(page.locator('#date_end_txt')).toContainText('2026-01-01 01:00');
    await expect(page.locator('#total_KWh_txt')).toContainText('25.000');
    await expect(page.locator('#meter_consumption_html_table')).toContainText('2026-01-01 00:00:00');
    await expect(page.locator('#meter_consumption_html_table')).toContainText('2026-01-01 01:00:00');
    await expect(page.locator('#meter_consumption_html_table')).toContainText('25.000');
    await expect(page.getByRole('button', { name: /Excel/ })).toBeVisible();

    const popupPromise = page.waitForEvent('popup');
    await page.getByRole('button', { name: /Excel/ }).click();
    const popup = await popupPromise;
    expect(popup).toBeTruthy();
    await popup.close();
  });

  test('admin can generate the hourly demand report for seeded meter readings', async ({ page }) => {
    await openDemandReportParameters(page);
    await fillDemandReportParameters(page);

    const demandReportResponsePromise = page.waitForResponse(response =>
      response.url().includes('/generate_demand_report/hourly') && response.status() === 200
    );

    await page.locator('#generate_raw_report').click();
    const demandReportResponse = await demandReportResponsePromise;
    const demandReportPayload = await demandReportResponse.json();

    expect(demandReportPayload.recordsTotal).toBe(1);
    expect(demandReportPayload.data[0]).toMatchObject({
      hour: '2026-01-01 00:00:00',
      building_code: 'SITEA',
      min_datetime: '2026-01-01 00:00:00',
      min_wh_total: '100.00',
      max_datetime: '2026-01-01 01:00:00',
      max_wh_total: '125.00',
      kw_demand: '25.00',
      multiplier: 1,
    });

    await expect(page.locator('#GenerateRAWReportModal')).toBeHidden();
    await expect(page.locator('#meter_description')).toContainText('MTR-001');
    await expect(page.locator('#tenant_name')).toContainText('Tenant A');
    await expect(page.locator('#gateway_description')).toContainText('GW-SN-001');
    await expect(page.locator('#building_code')).toContainText('SITEA');
    await expect(page.locator('#building_name')).toContainText('Accessible Building');
    await expect(page.locator('#date_start_txt')).toContainText('2026-01-01 00:00');
    await expect(page.locator('#date_end_txt')).toContainText('2026-01-01 01:00');
    await expect(page.locator('#meter_demand_html_table')).toContainText('2026-01-01 00:00:00');
    await expect(page.locator('#meter_demand_html_table')).toContainText('2026-01-01 01:00:00');
    await expect(page.locator('#meter_demand_html_table')).toContainText('25.000');
    await expect(page.getByRole('button', { name: /Excel/ })).toBeVisible();

    const popupPromise = page.waitForEvent('popup');
    await page.getByRole('button', { name: /Excel/ }).click();
    const popup = await popupPromise;
    expect(popup).toBeTruthy();
    await popup.close();
  });

  test('admin can open user maintenance and receive the seeded user list contract', async ({ page }) => {
    const userListPayload = await openUserMaintenance(page);

    expect(userListPayload.recordsTotal).toBe(2);
    expect(userListPayload.data.map(row => row.user_name)).toEqual(['admin', 'scoped']);
    expect(userListPayload.data[0]).toMatchObject({
      user_real_name: 'DEC',
      user_name: 'admin',
      user_type: 'Admin',
      user_access: 'ALL',
      user_email_address: 'admin@example.test',
    });
    expect(userListPayload.data[1]).toMatchObject({
      user_real_name: 'Scoped User',
      user_name: 'scoped',
      user_type: 'User',
      user_access: 'Selected',
      user_email_address: 'scoped@example.test',
    });
    expect(userListPayload.data[0].action).not.toContain('UserAccess');
    expect(userListPayload.data[1].action).toContain('UserAccess');
  });

  test('admin sees legacy validation messages when creating an empty user', async ({ page }) => {
    await openUserMaintenance(page);

    await page.locator('button[data-bs-target="#CreateUserModal"]').click();
    await expect(page.locator('#CreateUserModal')).toBeVisible();

    const createUserResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_user_post') && response.status() === 422
    );

    await page.locator('#save-user').click();
    await createUserResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#user_real_nameError')).toContainText('Name is Required');
    await expect(page.locator('#user_nameError')).toContainText('User Name is Required');
    await expect(page.locator('#user_email_address_managementError')).toContainText('Email Address is Required');
    await expect(page.locator('#user_passwordError')).toContainText('Password is Required');
    await expect(page.locator('#user_typeError')).toContainText('User Type is Required');
  });

  test('admin can view scoped user building access without changing it', async ({ page }) => {
    const userListPayload = await openUserMaintenance(page);
    const scopedUser = userListPayload.data.find(row => row.user_name === scopedUsername);

    expect(scopedUser).toBeTruthy();

    const siteAccessResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_site_access') && response.status() === 200
    );
    const userInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_info') && response.status() === 200
    );

    await page.evaluate(userId => {
      window.UpdateUserAccess(userId);
    }, scopedUser.user_id);

    const siteAccessResponse = await siteAccessResponsePromise;
    await userInfoResponsePromise;
    const siteAccessPayload = await siteAccessResponse.json();

    expect(siteAccessPayload.recordsTotal).toBe(2);
    expect(siteAccessPayload.data.map(row => row.building_code)).toEqual(['SITEA', 'SITEB']);
    expect(siteAccessPayload.data[0].action).toContain('checked');
    expect(siteAccessPayload.data[1].action).not.toContain('checked');

    await expect(page.locator('#SiteUserAccessModal')).toBeVisible();
    await expect(page.locator('#user_real_name_info_site_access')).toContainText('Scoped User');
    await expect(page.locator('#user_name_info_site_access')).toContainText('scoped');
    await expect(page.locator('#user_type_info_site_access')).toContainText('User');
    await expect(page.locator('#UserSiteAccessList')).toContainText('SITEA');
    await expect(page.locator('#UserSiteAccessList')).toContainText('SITEB');
    await expect(page.locator('#CheckboxGroup1_1')).toBeChecked();
    await expect(page.locator('#CheckboxGroup1_2')).not.toBeChecked();
    await expect(page.locator('#update-user-site-access')).toBeDisabled();
  });

  test('admin can open division maintenance and receive the seeded division list contract', async ({ page }) => {
    const divisionListPayload = await openDivisionMaintenance(page);

    expect(divisionListPayload.recordsTotal).toBe(1);
    expect(divisionListPayload.data[0]).toMatchObject({
      division_code: 'DIV',
      division_name: 'Characterization Division',
    });
    expect(divisionListPayload.data[0].action).toContain('editDivision');
    expect(divisionListPayload.data[0].action).toContain('deleteDivision');
  });

  test('admin sees legacy validation messages when creating an empty division', async ({ page }) => {
    await openDivisionMaintenance(page);

    await page.locator('button[data-bs-target="#CreateDivisionModal"]').click();
    await expect(page.locator('#CreateDivisionModal')).toBeVisible();

    const createDivisionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_division_post') && response.status() === 422
    );

    await page.locator('#save-division').click();
    await createDivisionResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#division_codeError')).toContainText('Division Code is Required');
    await expect(page.locator('#division_nameError')).toContainText('Division Name is Required');
  });

  test('admin can open the seeded division update modal without saving changes', async ({ page }) => {
    const divisionListPayload = await openDivisionMaintenance(page);
    const seededDivision = divisionListPayload.data.find(row => row.division_code === 'DIV');

    expect(seededDivision).toBeTruthy();

    const divisionInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/division_info') && response.status() === 200
    );

    await page.locator(`#divisionList a#editDivision[data-id="${seededDivision.division_id}"]`).click();
    const divisionInfoResponse = await divisionInfoResponsePromise;
    const divisionInfoPayload = await divisionInfoResponse.json();

    expect(divisionInfoPayload).toMatchObject({
      division_code: 'DIV',
      division_name: 'Characterization Division',
    });

    await expect(page.locator('#UpdateDivisionModal')).toBeVisible();
    await expect(page.locator('#update_division_code')).toHaveValue('DIV');
    await expect(page.locator('#update_division_name')).toHaveValue('Characterization Division');
    await expect(page.locator('#update-division')).toBeDisabled();
  });

  test('admin can create, reject duplicate, update, and delete a disposable division', async ({ page }) => {
    const uniqueSuffix = Date.now().toString().slice(-8);
    const createdDivisionCode = `D${uniqueSuffix}`;
    const createdDivisionName = `Browser Disposable Division ${uniqueSuffix}`;
    const updatedDivisionCode = `U${uniqueSuffix}`;
    const updatedDivisionName = `${createdDivisionName} Updated`;

    await openDivisionMaintenance(page);

    await page.locator('button[data-bs-target="#CreateDivisionModal"]').click();
    await expect(page.locator('#CreateDivisionModal')).toBeVisible();
    await page.locator('#division_code').fill(createdDivisionCode);
    await page.locator('#division_name').fill(createdDivisionName);

    const createDivisionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_division_post') && response.status() === 200
    );
    const createdDivisionListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/division_list') && response.status() === 200
    );

    await page.locator('#save-division').click();
    const createDivisionResponse = await createDivisionResponsePromise;
    const createDivisionPayload = await createDivisionResponse.json();
    const createdDivisionListPayload = await (await createdDivisionListResponsePromise).json();
    const createdDivision = createdDivisionListPayload.data.find(row =>
      row.division_code === createdDivisionCode && row.division_name === createdDivisionName
    );

    expect(createDivisionPayload).toEqual({
      success: 'Division Information Successfully Created!',
    });
    expect(createdDivision).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Division Information Successfully Created!');
    await closeSuccessModal(page);
    await expect(page.locator('#divisionList')).toContainText(createdDivisionName);

    await page.locator('#division_code').fill(createdDivisionCode);
    await page.locator('#division_name').fill(createdDivisionName);
    const duplicateDivisionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_division_post') && response.status() === 422
    );

    await page.locator('#save-division').click();
    await duplicateDivisionResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#division_codeError')).toContainText(`${createdDivisionCode} has already been taken.`);
    await expect(page.locator('#division_nameError')).toContainText(`${createdDivisionName} has already been taken.`);
    await page.locator('#InvalidModalBtn').click();
    await expect(page.locator('#InvalidModal')).toBeHidden();

    await page.locator('#CreateDivisionModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#CreateDivisionModal')).toBeHidden();

    const divisionInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/division_info') && response.status() === 200
    );

    await page.locator(`#divisionList a#editDivision[data-id="${createdDivision.division_id}"]`).click();
    const divisionInfoResponse = await divisionInfoResponsePromise;
    const divisionInfoPayload = await divisionInfoResponse.json();

    expect(divisionInfoPayload).toMatchObject({
      division_code: createdDivisionCode,
      division_name: createdDivisionName,
    });
    await expect(page.locator('#UpdateDivisionModal')).toBeVisible();
    await expect(page.locator('#update-division')).toBeDisabled();

    const changedDivisionInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/division_info') && response.status() === 200
    );
    await page.locator('#update_division_code').fill(updatedDivisionCode);
    await page.locator('#update_division_code').dispatchEvent('change');
    await changedDivisionInfoResponsePromise;
    await page.locator('#update_division_name').fill(updatedDivisionName);
    await page.locator('#update_division_name').dispatchEvent('change');
    await expect(page.locator('#update-division')).toBeEnabled();

    const updateDivisionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/update_division_post') && response.status() === 200
    );
    const updatedDivisionListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/division_list') && response.status() === 200
    );

    await page.locator('#update-division').click();
    const updateDivisionResponse = await updateDivisionResponsePromise;
    const updateDivisionPayload = await updateDivisionResponse.json();
    const updatedDivisionListPayload = await (await updatedDivisionListResponsePromise).json();
    const updatedDivision = updatedDivisionListPayload.data.find(row =>
      row.division_code === updatedDivisionCode && row.division_name === updatedDivisionName
    );

    expect(updateDivisionPayload).toEqual({
      success: 'Division Information Successfully Updated!',
    });
    expect(updatedDivision).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Division Information Successfully Updated!');
    await closeSuccessModal(page);
    await page.locator('#UpdateDivisionModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#UpdateDivisionModal')).toBeHidden();
    await expect(page.locator('#divisionList')).toContainText(updatedDivisionName);

    const deleteInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/division_info') && response.status() === 200
    );

    await page.locator(`#divisionList a#deleteDivision[data-id="${updatedDivision.division_id}"]`).click();
    await deleteInfoResponsePromise;

    await expect(page.locator('#DivisionDeleteModal')).toBeVisible();
    await expect(page.locator('#division_name_info_confirm')).toContainText(updatedDivisionName);

    const deleteDivisionResponsePromise = page.waitForResponse(response =>
      response.url().includes('/delete_division_confirmed') && response.status() === 200
    );
    const deletedDivisionListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/division_list') && response.status() === 200
    );

    await page.locator('#deleteDivisionConfirmed').click();
    const deleteDivisionResponse = await deleteDivisionResponsePromise;
    const deletedDivisionListPayload = await (await deletedDivisionListResponsePromise).json();

    expect(await deleteDivisionResponse.text()).toBe('Deleted');
    expect(deletedDivisionListPayload.data.map(row => row.division_name)).not.toContain(updatedDivisionName);
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Division Deleted!');
    await closeSuccessModal(page);
    await expect(page.locator('#divisionList')).not.toContainText(updatedDivisionName);
  });

  test('admin can open configuration file maintenance and receive the seeded list contract', async ({ page }) => {
    const configurationFileListPayload = await openConfigurationFileMaintenance(page);

    expect(configurationFileListPayload.recordsTotal).toBe(1);
    expect(configurationFileListPayload.data[0]).toMatchObject({
      config_file: 'zmd402.cfg',
    });
    expect(configurationFileListPayload.data[0].action).toContain('editconfiguration_file');
    expect(configurationFileListPayload.data[0].action).toContain('deleteconfiguration_file');
  });

  test('admin sees legacy validation messages when creating an empty configuration file', async ({ page }) => {
    await openConfigurationFileMaintenance(page);

    await page.locator('button[data-bs-target="#Createconfiguration_fileModal"]').click();
    await expect(page.locator('#Createconfiguration_fileModal')).toBeVisible();

    const createConfigurationFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_configuration_file_post') && response.status() === 422
    );

    await page.locator('#save-configuration_file').click();
    await createConfigurationFileResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#configuration_file_nameError')).toContainText('File Name is Required');
  });

  test('admin can open the seeded configuration file update modal without saving changes', async ({ page }) => {
    const configurationFileListPayload = await openConfigurationFileMaintenance(page);
    const seededConfigurationFile = configurationFileListPayload.data.find(row => row.config_file === 'zmd402.cfg');

    expect(seededConfigurationFile).toBeTruthy();

    const configurationFileInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_info') && response.status() === 200
    );

    await page.locator(`#configuration_fileList a#editconfiguration_file[data-id="${seededConfigurationFile.config_id}"]`).click();
    const configurationFileInfoResponse = await configurationFileInfoResponsePromise;
    const configurationFileInfoPayload = await configurationFileInfoResponse.json();

    expect(configurationFileInfoPayload).toMatchObject({
      config_file: 'zmd402.cfg',
    });

    await expect(page.locator('#Updateconfiguration_fileModal')).toBeVisible();
    await expect(page.locator('#update_configuration_file_name')).toHaveValue('zmd402.cfg');
    await expect(page.locator('#update-configuration_file')).toBeDisabled();
  });

  test('admin can create, reject duplicate, update, and delete a disposable configuration file', async ({ page }) => {
    const uniqueSuffix = Date.now().toString().slice(-8);
    const createdConfigurationFileName = `browser-disposable-${uniqueSuffix}.cfg`;
    const updatedConfigurationFileName = `browser-disposable-${uniqueSuffix}-updated.cfg`;

    await openConfigurationFileMaintenance(page);

    await page.locator('button[data-bs-target="#Createconfiguration_fileModal"]').click();
    await expect(page.locator('#Createconfiguration_fileModal')).toBeVisible();
    await page.locator('#configuration_file_name').fill(createdConfigurationFileName);

    const createConfigurationFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_configuration_file_post') && response.status() === 200
    );
    const createdConfigurationFileListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_list') && response.status() === 200
    );

    await page.locator('#save-configuration_file').click();
    const createConfigurationFileResponse = await createConfigurationFileResponsePromise;
    const createConfigurationFilePayload = await createConfigurationFileResponse.json();
    const createdConfigurationFileListPayload = await (await createdConfigurationFileListResponsePromise).json();
    const createdConfigurationFile = createdConfigurationFileListPayload.data.find(row =>
      row.config_file === createdConfigurationFileName
    );

    expect(createConfigurationFilePayload).toEqual({
      success: 'Configuration File Information Successfully Created!',
    });
    expect(createdConfigurationFile).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Configuration File Information Successfully Created!');
    await closeSuccessModal(page);
    await expect(page.locator('#configuration_fileList')).toContainText(createdConfigurationFileName);

    await page.locator('#configuration_file_name').fill(createdConfigurationFileName);
    const duplicateConfigurationFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_configuration_file_post') && response.status() === 422
    );

    await page.locator('#save-configuration_file').click();
    await duplicateConfigurationFileResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#configuration_file_nameError')).toContainText(`${createdConfigurationFileName} has already been taken.`);
    await page.locator('#InvalidModalBtn').click();
    await expect(page.locator('#InvalidModal')).toBeHidden();

    await page.locator('#Createconfiguration_fileModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#Createconfiguration_fileModal')).toBeHidden();

    const configurationFileInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_info') && response.status() === 200
    );

    await page.locator(`#configuration_fileList a#editconfiguration_file[data-id="${createdConfigurationFile.config_id}"]`).click();
    const configurationFileInfoResponse = await configurationFileInfoResponsePromise;
    const configurationFileInfoPayload = await configurationFileInfoResponse.json();

    expect(configurationFileInfoPayload).toMatchObject({
      config_file: createdConfigurationFileName,
    });
    await expect(page.locator('#Updateconfiguration_fileModal')).toBeVisible();
    await expect(page.locator('#update-configuration_file')).toBeDisabled();

    const changedConfigurationFileInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_info') && response.status() === 200
    );
    await page.locator('#update_configuration_file_name').fill(updatedConfigurationFileName);
    await page.locator('#update_configuration_file_name').dispatchEvent('change');
    await changedConfigurationFileInfoResponsePromise;
    await expect(page.locator('#update-configuration_file')).toBeEnabled();

    const updateConfigurationFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/update_configuration_file_post') && response.status() === 200
    );
    const updatedConfigurationFileListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_list') && response.status() === 200
    );

    await page.locator('#update-configuration_file').click();
    const updateConfigurationFileResponse = await updateConfigurationFileResponsePromise;
    const updateConfigurationFilePayload = await updateConfigurationFileResponse.json();
    const updatedConfigurationFileListPayload = await (await updatedConfigurationFileListResponsePromise).json();
    const updatedConfigurationFile = updatedConfigurationFileListPayload.data.find(row =>
      row.config_file === updatedConfigurationFileName
    );

    expect(updateConfigurationFilePayload).toEqual({
      success: 'Configuration File Successfully Updated!',
    });
    expect(updatedConfigurationFile).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Configuration File Successfully Updated!');
    await closeSuccessModal(page);
    await page.locator('#Updateconfiguration_fileModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#Updateconfiguration_fileModal')).toBeHidden();
    await expect(page.locator('#configuration_fileList')).toContainText(updatedConfigurationFileName);

    const deleteInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_info') && response.status() === 200
    );

    await page.locator(`#configuration_fileList a#deleteconfiguration_file[data-id="${updatedConfigurationFile.config_id}"]`).click();
    await deleteInfoResponsePromise;

    await expect(page.locator('#Configuration_fileDeleteModal')).toBeVisible();
    await expect(page.locator('#configuration_file_name_info_confirm')).toContainText(updatedConfigurationFileName);

    const deleteConfigurationFileResponsePromise = page.waitForResponse(response =>
      response.url().includes('/delete_configuration_file_confirmed') && response.status() === 200
    );
    const deletedConfigurationFileListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/configuration_file_list') && response.status() === 200
    );

    await page.locator('#deleteconfiguration_fileConfirmed').click();
    const deleteConfigurationFileResponse = await deleteConfigurationFileResponsePromise;
    const deletedConfigurationFileListPayload = await (await deletedConfigurationFileListResponsePromise).json();

    expect(await deleteConfigurationFileResponse.text()).toBe('Deleted');
    expect(deletedConfigurationFileListPayload.data.map(row => row.config_file)).not.toContain(updatedConfigurationFileName);
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Configuration File Deleted!');
    await closeSuccessModal(page);
    await expect(page.locator('#configuration_fileList')).not.toContainText(updatedConfigurationFileName);
  });

  test('admin can open company maintenance and receive the seeded company list contract', async ({ page }) => {
    const companyListPayload = await openCompanyMaintenance(page);

    expect(companyListPayload.recordsTotal).toBe(1);
    expect(companyListPayload.data[0]).toMatchObject({
      company_name: 'Characterization Company',
    });
    expect(companyListPayload.data[0].action).toContain('editCompany');
    expect(companyListPayload.data[0].action).toContain('deleteCompany');
  });

  test('admin sees legacy validation messages when creating an empty company', async ({ page }) => {
    await openCompanyMaintenance(page);

    await page.locator('button[data-bs-target="#CreateCompanyModal"]').click();
    await expect(page.locator('#CreateCompanyModal')).toBeVisible();

    const createCompanyResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_company_post') && response.status() === 422
    );

    await page.locator('#save-company').click();
    await createCompanyResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#company_nameError')).toContainText('Company Name is Required');
  });

  test('admin can open the seeded company update modal without saving changes', async ({ page }) => {
    const companyListPayload = await openCompanyMaintenance(page);
    const seededCompany = companyListPayload.data.find(row => row.company_name === 'Characterization Company');

    expect(seededCompany).toBeTruthy();

    const companyInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/company_info') && response.status() === 200
    );

    await page.locator(`#companyList a#editCompany[data-id="${seededCompany.company_id}"]`).click();
    const companyInfoResponse = await companyInfoResponsePromise;
    const companyInfoPayload = await companyInfoResponse.json();

    expect(companyInfoPayload).toMatchObject({
      company_name: 'Characterization Company',
      company_code: 'COMP',
    });

    await expect(page.locator('#UpdateCompanyModal')).toBeVisible();
    await expect(page.locator('#update_company_name')).toHaveValue('Characterization Company');
    await expect(page.locator('#update-company')).toBeDisabled();
  });

  test('admin can create, reject duplicate, update, and delete a disposable company', async ({ page }) => {
    const createdCompanyName = `Browser Disposable Company ${Date.now()}`;
    const updatedCompanyName = `${createdCompanyName} Updated`;

    await openCompanyMaintenance(page);

    await page.locator('button[data-bs-target="#CreateCompanyModal"]').click();
    await expect(page.locator('#CreateCompanyModal')).toBeVisible();
    await page.locator('#company_name').fill(createdCompanyName);

    const createCompanyResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_company_post') && response.status() === 200
    );
    const createdCompanyListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/company_list') && response.status() === 200
    );

    await page.locator('#save-company').click();
    const createCompanyResponse = await createCompanyResponsePromise;
    const createCompanyPayload = await createCompanyResponse.json();
    const createdCompanyListPayload = await (await createdCompanyListResponsePromise).json();
    const createdCompany = createdCompanyListPayload.data.find(row => row.company_name === createdCompanyName);

    expect(createCompanyPayload).toEqual({
      success: 'Company Information Successfully Created!',
    });
    expect(createdCompany).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Company Information Successfully Created!');
    await closeSuccessModal(page);
    await expect(page.locator('#companyList')).toContainText(createdCompanyName);

    await page.locator('#company_name').fill(createdCompanyName);
    const duplicateCompanyResponsePromise = page.waitForResponse(response =>
      response.url().includes('/create_company_post') && response.status() === 422
    );

    await page.locator('#save-company').click();
    await duplicateCompanyResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#company_nameError')).toContainText(`${createdCompanyName} has already been taken.`);
    await page.locator('#InvalidModalBtn').click();
    await expect(page.locator('#InvalidModal')).toBeHidden();

    await page.locator('#CreateCompanyModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#CreateCompanyModal')).toBeHidden();

    const companyInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/company_info') && response.status() === 200
    );

    await page.locator(`#companyList a#editCompany[data-id="${createdCompany.company_id}"]`).click();
    const companyInfoResponse = await companyInfoResponsePromise;
    const companyInfoPayload = await companyInfoResponse.json();

    expect(companyInfoPayload).toMatchObject({
      company_name: createdCompanyName,
    });
    await expect(page.locator('#UpdateCompanyModal')).toBeVisible();
    await expect(page.locator('#update-company')).toBeDisabled();

    const changedCompanyInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/company_info') && response.status() === 200
    );
    await page.locator('#update_company_name').fill(updatedCompanyName);
    await page.locator('#update_company_name').dispatchEvent('change');
    await changedCompanyInfoResponsePromise;
    await expect(page.locator('#update-company')).toBeEnabled();

    const updateCompanyResponsePromise = page.waitForResponse(response =>
      response.url().includes('/update_company_post') && response.status() === 200
    );
    const updatedCompanyListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/company_list') && response.status() === 200
    );

    await page.locator('#update-company').click();
    const updateCompanyResponse = await updateCompanyResponsePromise;
    const updateCompanyPayload = await updateCompanyResponse.json();
    const updatedCompanyListPayload = await (await updatedCompanyListResponsePromise).json();
    const updatedCompany = updatedCompanyListPayload.data.find(row => row.company_name === updatedCompanyName);

    expect(updateCompanyPayload).toEqual({
      success: 'Company Information Successfully Updated!',
    });
    expect(updatedCompany).toBeTruthy();
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Company Information Successfully Updated!');
    await closeSuccessModal(page);
    await page.locator('#UpdateCompanyModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#UpdateCompanyModal')).toBeHidden();
    await expect(page.locator('#companyList')).toContainText(updatedCompanyName);

    const deleteInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/company_info') && response.status() === 200
    );

    await page.locator(`#companyList a#deleteCompany[data-id="${updatedCompany.company_id}"]`).click();
    await deleteInfoResponsePromise;

    await expect(page.locator('#CompanyDeleteModal')).toBeVisible();
    await expect(page.locator('#company_name_info_confirm')).toContainText(updatedCompanyName);

    const deleteCompanyResponsePromise = page.waitForResponse(response =>
      response.url().includes('/delete_company_confirmed') && response.status() === 200
    );
    const deletedCompanyListResponsePromise = page.waitForResponse(response =>
      response.url().includes('/company_list') && response.status() === 200
    );

    await page.locator('#deleteCompanyConfirmed').click();
    const deleteCompanyResponse = await deleteCompanyResponsePromise;
    const deletedCompanyListPayload = await (await deletedCompanyListResponsePromise).json();

    expect(await deleteCompanyResponse.text()).toBe('Deleted');
    expect(deletedCompanyListPayload.data.map(row => row.company_name)).not.toContain(updatedCompanyName);
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Company Deleted!');
    await closeSuccessModal(page);
    await expect(page.locator('#companyList')).not.toContainText(updatedCompanyName);
  });

  test('admin sees legacy validation messages when account settings are emptied', async ({ page }) => {
    await loginAsAdmin(page);

    const userInfoPayload = await openAccountSettings(page);

    expect(userInfoPayload).toMatchObject({
      user_real_name: 'DEC',
      user_name: 'admin',
      user_email_address: 'admin@example.test',
    });

    await expect(page.locator('#account_user_real_name')).toHaveValue('DEC');
    await expect(page.locator('#account_user_name')).toHaveValue('admin');
    await expect(page.locator('#user_email_address')).toHaveValue('admin@example.test');
    await expect(page.locator('#account-user')).toBeDisabled();

    const changedUserInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_info') && response.status() === 200
    );

    await page.locator('#account_user_real_name').fill('');
    await page.locator('#account_user_name').fill('');
    await page.locator('#user_email_address').fill('');
    await page.locator('#account_user_real_name').dispatchEvent('change');
    await changedUserInfoResponsePromise;
    await expect(page.locator('#account-user')).toBeEnabled();

    const accountValidationResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_account_post') && response.status() === 422
    );

    await page.locator('#account-user').click();
    await accountValidationResponsePromise;

    await expect(page.locator('#InvalidModal')).toBeVisible();
    await expect(page.locator('#account_user_real_nameError')).toContainText('Name is required');
    await expect(page.locator('#account_user_nameError')).toContainText('User Name is Required');
    await expect(page.locator('#user_email_addressError')).toContainText('The user email address field is required.');
    await page.locator('#InvalidModalBtn').click();
    await expect(page.locator('#InvalidModal')).toBeHidden();
    await page.locator('#UserProfileModal button[data-bs-dismiss="modal"]').click();
    await expect(page.locator('#UserProfileModal')).toBeHidden();
  });

  test('admin can update and restore account profile fields without changing password', async ({ page }) => {
    const updatedRealName = `DEC Browser ${Date.now()}`;
    const updatedEmail = `admin.browser.${Date.now()}@example.test`;

    await loginAsAdmin(page);

    await openAccountSettings(page);

    const changedUserInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_info') && response.status() === 200
    );

    await page.locator('#account_user_real_name').fill(updatedRealName);
    await page.locator('#user_email_address').fill(updatedEmail);
    await page.locator('#account_user_real_name').dispatchEvent('change');
    await changedUserInfoResponsePromise;
    await expect(page.locator('#account-user')).toBeEnabled();

    const updateAccountResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_account_post') && response.status() === 200
    );

    await page.locator('#account-user').click();
    const updateAccountResponse = await updateAccountResponsePromise;
    const updateAccountPayload = await updateAccountResponse.json();

    expect(updateAccountPayload).toEqual({
      success: 'Account Information Successfully Updated!',
    });
    await expect(page.locator('#SuccessModal')).toBeVisible();
    await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Account Information Successfully Updated!');
    await expect(page.locator('#UserProfileModal')).toBeHidden();
    await closeSuccessModal(page);

    await page.reload();
    await expect(page.locator('.top_navbar_text')).toContainText(updatedRealName);

    const updatedUserInfoPayload = await openAccountSettings(page);

    expect(updatedUserInfoPayload).toMatchObject({
      user_real_name: updatedRealName,
      user_name: 'admin',
      user_email_address: updatedEmail,
    });

    const restoreUserInfoResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_info') && response.status() === 200
    );

    await page.locator('#account_user_real_name').fill('DEC');
    await page.locator('#user_email_address').fill('admin@example.test');
    await page.locator('#account_user_real_name').dispatchEvent('change');
    await restoreUserInfoResponsePromise;
    await expect(page.locator('#account-user')).toBeEnabled();

    const restoreAccountResponsePromise = page.waitForResponse(response =>
      response.url().includes('/user_account_post') && response.status() === 200
    );

    await page.locator('#account-user').click();
    const restoreAccountResponse = await restoreAccountResponsePromise;
    const restoreAccountPayload = await restoreAccountResponse.json();

    expect(restoreAccountPayload).toEqual({
      success: 'Account Information Successfully Updated!',
    });
    await expect(page.locator('#UserProfileModal')).toBeHidden();
    await closeSuccessModal(page);

    await page.reload();
    await expect(page.locator('.top_navbar_text')).toContainText('DEC');

    const restoredUserInfoPayload = await openAccountSettings(page);

    expect(restoredUserInfoPayload).toMatchObject({
      user_real_name: 'DEC',
      user_name: 'admin',
      user_email_address: 'admin@example.test',
    });
  });

  test('known-email password reset mutates the password and can be restored without sending real mail', async ({ page, browser }) => {
    let resetWasTriggered = false;

    await loginAsAdmin(page);

    try {
      await page.goto('/passwordreset');
      await page.locator('#user_email_address').fill('admin@example.test');

      const resetResponsePromise = page.waitForResponse(response =>
        response.url().includes('/reset-password') && response.status() === 200
      );

      await page.locator('#check-email').click();
      const resetResponse = await resetResponsePromise;
      const resetPayload = await resetResponse.json();
      resetWasTriggered = true;

      expect(resetPayload).toEqual({
        success: 'Email sent successfully!',
      });
      await expect(page.locator('#SuccessModal')).toBeVisible();
      await expect(page.locator('#SuccessModal .success_modal_bg')).toContainText('Email sent successfully!');

      const oldPasswordContext = await browser.newContext({ baseURL });
      const oldPasswordPage = await oldPasswordContext.newPage();

      await oldPasswordPage.goto('/');
      await oldPasswordPage.locator('#user_name').fill(adminUsername);
      await oldPasswordPage.locator('#InputPassword').fill(adminPassword);
      await oldPasswordPage.getByRole('button', { name: 'Login' }).click();

      await expect(oldPasswordPage).toHaveURL(/\/$/);
      await expect(oldPasswordPage.getByText('Incorrect Password')).toBeVisible();
      await oldPasswordContext.close();
    } finally {
      if (resetWasTriggered) {
        await page.goto('/site');
        await openAccountSettings(page);

        const passwordChangeCheckResponsePromise = page.waitForResponse(response =>
          response.url().includes('/user_info') && response.status() === 200
        );

        await page.locator('#account_user_password').fill(adminPassword);
        await page.locator('#account_user_password').dispatchEvent('change');
        await passwordChangeCheckResponsePromise;
        await expect(page.locator('#account-user')).toBeEnabled();

        const restoreAccountResponsePromise = page.waitForResponse(response =>
          response.url().includes('/user_account_post') && response.status() === 200
        );

        await page.locator('#account-user').click();
        const restoreAccountResponse = await restoreAccountResponsePromise;
        const restoreAccountPayload = await restoreAccountResponse.json();

        expect(restoreAccountPayload).toEqual({
          success: 'Account Information Successfully Updated!',
        });
        await expect(page.locator('#UserProfileModal')).toBeHidden();
        await closeSuccessModal(page);
      }
    }

    const restoredPasswordContext = await browser.newContext({ baseURL });
    const restoredPasswordPage = await restoredPasswordContext.newPage();

    await loginAsAdmin(restoredPasswordPage);
    await expect(restoredPasswordPage.locator('#siteList')).toBeVisible();
    await restoredPasswordContext.close();
  });
});
