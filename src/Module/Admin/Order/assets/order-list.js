/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import '@main';

u.$ui.bootstrap.tooltip();

const formSelector = '#admin-form';

// Init Grid
u.grid(formSelector).initComponent();

// Disable on submit
u.$ui.disableOnSubmit(formSelector);

// Checkbox Multi-select
u.$ui.checkboxesMultiSelect(formSelector);

// Print
u.selectOne('[data-task=print_list]')?.addEventListener('click', (e) => {
  /** @type HTMLButtonElement */
  const button = e.currentTarget;
  let uri = button.dataset.uri;
  const ids = u.grid(formSelector).getCheckedValues();
  
  if (ids.length) {
    uri = u.$router.addQuery(uri, { id: ids });
  }

  window.open(uri);
});
