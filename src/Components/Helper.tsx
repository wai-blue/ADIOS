import React from 'react';
import Notification from "./Notification";

export function setUrlParam(paramName, paramValue) {
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.set(paramName, paramValue);
  window.history.pushState({}, "", '?' + urlParams.toString());
}

export function getUrlParam(paramName): any {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.has(paramName) ? urlParams.get(paramName) : null;
}

export function deleteUrlParam(paramName): any {
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.delete(paramName);
  if (Array.from(urlParams).length == 0) {
    console.log('delete')
    history.pushState({}, '', location.protocol + "//" + location.host + location.pathname);
  } else {
    window.history.pushState({}, "", '?' + urlParams.toString())
  }
}

export function deepObjectMerge(target: any, source: any): Object {
  let result: any = target;
  
  for (const key in source) {
    if (source[key] instanceof Object) {
      if (!(result[key] instanceof Object)) {
        result[key] = source[key];
      } else {
        result[key] = deepObjectMerge(result[key], source[key]);
      }
    } else {
      result[key] = source[key]; // target will be overwritten by source
    }
  }

  // for (const key in source) {
  //   if (target[key] == undefined) {
  //     result[key] = source[key]; // target will be overwritten by source
  //   }
  // }

  return result;
}

export function adiosError(message: string): JSX.Element {
  //Notification.error(message);
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
