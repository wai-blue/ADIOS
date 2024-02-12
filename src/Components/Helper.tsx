import React from "react";
import Notification from "./Notification";

export function deepObjectMerge(target: any, source: any): Object {
  for (const key in source) {
    if (source.hasOwnProperty(key)) {
      if (source[key] instanceof Object && key in target) {
        deepObjectMerge(target[key], source[key]);
      } else if (source[key] != undefined) {
        target[key] = source[key];
      } else {
        target[key] = target[key];
      }
    }
  }

  return target;
}

export function adiosError(message: string): JSX.Element {
  // Notification.error(htmlText);

  console.error('ADIOS: ' + message);

  return (
    <div className="alert alert-danger" role="alert">
      {message}
    </div>
  );
}

export function dateToString(date: Date): string {
  return `${date.getDate()}.${date.getMonth()+1}.${date.getFullYear()}`;
}

export function numberToStringTime(number: number): string {
  return String(number).padStart(2, '0');
}

/**
 * Validate string if it is JSON
 */
export function isValidJson(jsonString: string) {
  try {
    let tmp = JSON.parse(jsonString);

    if (tmp && typeof tmp === "object") {
      return true;
    } else {
      return false;
    }
  } catch (error) {
    return false;
  }
}

export function capitalizeFirstLetter(s: string) { return s.charAt(0).toUpperCase() + s.slice(1) };
export function kebabToCamel(s: string) { return s.replace(/-./g, x=>x[1].toUpperCase()) };
export function kebabToPascal(s: string) { return capitalizeFirstLetter(kebabToCamel(s)) };
export function camelToKebab(s: string) { return s.replace(/[A-Z]+(?![a-z])|[A-Z]/g, ($, ofs) => (ofs ? "-" : "") + $.toLowerCase()); }
