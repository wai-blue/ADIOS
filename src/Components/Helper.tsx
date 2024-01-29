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

export function adiosError(htmlText: string): JSX.Element {
  Notification.error(htmlText);

  return (
    <div className="alert alert-danger" role="alert">
      <div dangerouslySetInnerHTML={{ __html: htmlText }} />
    </div>
  );
}

export function dateToString(date: Date): string {
  return `${date.getDate()}.${date.getMonth()+1}.${date.getFullYear()}`;
}
