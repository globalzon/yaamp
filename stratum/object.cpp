
#include "stratum.h"

YAAMP_OBJECT *object_find(CommonList *list, int id, bool lock)
{
	if(lock) list->Enter();
	for(CLI li = list->first; li; li = li->next)
	{
		YAAMP_OBJECT *object = (YAAMP_OBJECT *)li->data;
		if(object->id == id)
		{
			if(lock)
			{
				object_lock(object);
				list->Leave();
			}

			return object;
		}
	}

	if(lock) list->Leave();
	return NULL;
}

void object_lock(YAAMP_OBJECT *object)
{
	if(!object) return;
	object->lock_count++;
}

void object_unlock(YAAMP_OBJECT *object)
{
	if(!object) return;
	object->lock_count--;
}

void object_delete(YAAMP_OBJECT *object)
{
	if(!object) return;
	object->deleted = true;
}

void object_prune(CommonList *list, YAAMP_OBJECT_DELETE_FUNC deletefunc)
{
	list->Enter();
	for(CLI li = list->first; li; )
	{
		YAAMP_OBJECT *object = (YAAMP_OBJECT *)li->data;
		li = li->next;

//		if(object->deleted && object->locked)
//			debuglog("object set for delete is locked\n");

		if(object->deleted && !object->lock_count)
		{
			list->Delete(object);
			deletefunc(object);
		}

		else if(object->lock_count && object->unlock)
			object->lock_count--;
	}

//	debuglog("still %d objects in list\n", list->count);
	list->Leave();
}






