// Defines Drupal global

export const Drupal: drupal.Drupal;

export type Translate = (text: string, variables?: any) => string;

declare namespace drupal {
    interface Drupal {
        readonly behaviors: any;
        readonly t: Translate;
        attachBehaviors(element: Element): void;
    }
}

export default Drupal;
