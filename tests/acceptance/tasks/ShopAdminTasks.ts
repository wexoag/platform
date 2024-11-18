import { mergeTests } from '@playwright/test';

/**
 * Media
 */
import { UploadImage } from './ShopAdmin/Product/UploadImage';

/**
 * Product
 */
import { GenerateVariants } from './ShopAdmin/Product/GenerateVariants';

/**
 * First Run Wizard
 */
import { FRWSalesChannelSelectionPossibility } from '@tasks/ShopAdmin/FRW/FRWSalesChannelSelectionPossibility';

/**
 * CustomField
 */
import { CreateCustomField } from '@tasks/ShopAdmin/CustomField/CreateCustomField';

/**
 * Add Landing Page From Category
 */
import { CreateLandingPage } from '@tasks/ShopAdmin/Category/CreateLandingPage';

export const test = mergeTests(
    GenerateVariants,
    UploadImage,
    FRWSalesChannelSelectionPossibility,
    CreateCustomField,
    CreateLandingPage,
);
